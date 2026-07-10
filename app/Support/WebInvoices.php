<?php

namespace App\Support;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Collection;

/**
 * Read layer for the customer-portal invoices page: maps the shared `invoices`
 * rows (written by rgadmin's `invoices:sync`) into the view shape the existing
 * `pages.invoices` Blade expects, plus a real PDF download URL.
 *
 * Paid status comes from the settlement `payments` ledger, NOT the `invoices.paid`
 * column (which staff never maintain): an invoice's month is paid iff a
 * `settlement` payment exists for (payable, month); a `credit` payment (partial
 * payment) reduces the still-owed `outstanding`. So a month a staff member
 * settled in the rgadmin Tartozások matrix is no longer debt here.
 *
 * STORNO handling: rgadmin pairs a storno with the invoice it cancels
 * (`corrects_invoice_id`). The storno document itself is HIDDEN from the list
 * (an unpaired storno too - it is never a claim); a cancelled original stays
 * listed with `status = 'cancelled'` and 0 outstanding ("Sztornózva" badge).
 *
 * With `$detail` (the Számláim page modals) each row also carries the real
 * invoice document: the line items (`lines`), the buyer (`buyer`), the issuer
 * (`seller`) and the net/vat totals - all from the parsed `xml_data` JSON +
 * the normalized columns, never synthesized.
 *
 * Always scoped to ONE customer (the authenticated `customers_id`); the model's
 * `forCustomer` scope is the single ownership filter.
 *
 * @example
 *   $rows = WebInvoices::forCustomer($user->customers_id, true);   // with document detail
 *   $invoice = WebInvoices::find($id, $user->customers_id);        // ownership-checked
 */
class WebInvoices
{
	/** Hungarian month names (1-based). */
	private const MONTHS = [
		1 => 'január', 2 => 'február', 3 => 'március', 4 => 'április', 5 => 'május', 6 => 'június',
		7 => 'július', 8 => 'augusztus', 9 => 'szeptember', 10 => 'október', 11 => 'november', 12 => 'december',
	];

	/** Base columns for the list rows + the paid/outstanding math. */
	private const BASE_COLUMNS = [
		'id', 'invoice_number', 'invoice_kind', 'corrects_invoice_id',
		'payable_type', 'payable_id', 'period_start',
		'issue_date', 'due_date', 'gross', 'has_pdf',
	];

	/** Extra columns needed to render the full invoice document (the modal). */
	private const DETAIL_COLUMNS = [
		'net', 'vat', 'buyer_name', 'buyer_address', 'buyer_tax_number',
		'buyer_customer_number', 'contract_number', 'xml_data',
	];

	/**
	 * The customer's invoices as view rows (newest first). Each row carries the
	 * billed `amount` (gross) AND the `outstanding` still owed (gross minus any
	 * recorded partial payment; 0 once settled). With `$detail`, also the line
	 * items + buyer + seller + net/vat for the invoice-preview modal.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function forCustomer(int $customerId, bool $detail = false): array
	{
		if ($customerId <= 0) {
			return [];
		}

		$columns  = $detail ? array_merge(self::BASE_COLUMNS, self::DETAIL_COLUMNS) : self::BASE_COLUMNS;
		$invoices = Invoice::forCustomer($customerId)->get($columns);
		[$settled, $credits] = self::ledger($invoices);

		// Storno documents never render; the originals they cancel stay listed
		// as 'cancelled'. A storno outside this customer's set (unmatched, so
		// no customers_id) is caught by one extra lookup over the loaded ids.
		$cancelled = self::cancelledIds($invoices) + self::externalCancelledIds($invoices);

		return $invoices
			->reject(static fn (Invoice $i): bool => $i->invoice_kind === Invoice::KIND_STORNO)
			->map(static fn (Invoice $i): array => self::row($i, $settled, $credits, $detail, $cancelled))
			->values()
			->all();
	}

	/**
	 * The ids cancelled by the loaded storno rows (their `corrects_invoice_id`
	 * references) - the pure, DB-free part of the storno resolution.
	 *
	 * @param  Collection<int, Invoice>  $invoices
	 * @return array<int, true>  keyed by the cancelled invoice id
	 *
	 * @example  $cancelled = WebInvoices::cancelledIds($invoices);
	 */
	public static function cancelledIds(Collection $invoices): array
	{
		$out = [];
		foreach ($invoices as $i) {
			if ($i->invoice_kind === Invoice::KIND_STORNO && $i->corrects_invoice_id !== null) {
				$out[(int) $i->corrects_invoice_id] = true;
			}
		}

		return $out;
	}

	/**
	 * Cancelled-id lookup for stornos OUTSIDE the loaded set: one query over the
	 * loaded non-storno ids for live stornos pointing at them.
	 *
	 * @param  Collection<int, Invoice>  $invoices
	 * @return array<int, true>  keyed by the cancelled invoice id
	 */
	private static function externalCancelledIds(Collection $invoices): array
	{
		$ids = $invoices
			->reject(static fn (Invoice $i): bool => $i->invoice_kind === Invoice::KIND_STORNO)
			->pluck('id')
			->all();
		if ($ids === []) {
			return [];
		}

		$out = [];
		foreach (Invoice::query()
			->where('deleted', 0)
			->where('invoice_kind', Invoice::KIND_STORNO)
			->whereIn('corrects_invoice_id', $ids)
			->pluck('corrects_invoice_id') as $id) {
			$out[(int) $id] = true;
		}

		return $out;
	}

	/**
	 * Build one view row (the list shape; with `$detail` also the document parts).
	 * `status`: paid | pending | overdue | cancelled (storno-cancelled original).
	 *
	 * @param  array<string, true>  $settled
	 * @param  array<string, int>   $credits
	 * @param  array<int, true>     $cancelled  invoice ids cancelled by a live storno
	 * @return array<string, mixed>
	 */
	private static function row(Invoice $i, array $settled, array $credits, bool $detail, array $cancelled = []): array
	{
		$gross = (int) round((float) $i->gross);
		$key   = self::cellKey($i);

		if (isset($cancelled[(int) $i->id])) {
			// A storno-cancelled original is never a claim.
			$outstanding = 0;
			$status      = 'cancelled';
		} else {
			if ($key !== null && isset($settled[$key])) {
				$outstanding = 0;
			} else {
				$credit      = $key !== null ? ($credits[$key] ?? 0) : 0;
				$outstanding = max(0, $gross - $credit);
			}

			$due    = $i->due_date;
			$status = $outstanding === 0
				? 'paid'
				: ($due !== null && $due->isPast() ? 'overdue' : 'pending');
		}

		$row = [
			'id'          => (string) ($i->invoice_number ?: ('SZ-' . $i->id)),
			'period'      => self::period($i),
			'service'     => self::service($i),
			'issued'      => optional($i->issue_date)->format('Y-m-d') ?? '',
			'due'         => optional($i->due_date)->format('Y-m-d') ?? '',
			'amount'      => $gross,
			'outstanding' => $outstanding,
			'status'      => $status,
			'lines'       => [],
			'downloadUrl' => $i->has_pdf ? route('invoices.download', ['id' => $i->id]) : null,
		];

		return $detail ? array_merge($row, self::detail($i, $gross)) : $row;
	}

	/**
	 * The full invoice-document parts for the preview modal: the line items, the
	 * buyer + seller blocks and the net/vat totals - read from the parsed
	 * `xml_data` JSON and the normalized columns, never synthesized.
	 *
	 * @return array{lines: array<int, array<string, mixed>>, buyer: array<string, string>, seller: array<string, string>|null, net: int, vat: int}
	 */
	private static function detail(Invoice $i, int $gross): array
	{
		$xml   = (array) ($i->xml_data ?? []);
		$net   = (int) round((float) $i->net);
		$vat   = (int) round((float) $i->vat);
		$lines = self::lines($xml);

		// No parseable line items (e.g. a PDF-only invoice with no XML): show one
		// row from the stored totals so the modal still reflects real figures.
		if ($lines === []) {
			$lines = [[
				'name'  => self::service($i) . ($i->contract_number ? ' (' . $i->contract_number . ')' : ''),
				'qty'   => '',
				'net'   => $net,
				'vat'   => $vat,
				'gross' => $gross,
			]];
		}

		return [
			'lines'  => $lines,
			'buyer'  => [
				'name'    => (string) ($i->buyer_name ?? ''),
				'address' => (string) ($i->buyer_address ?? ''),
				'tax'     => (string) ($i->buyer_tax_number ?? ''),
				'number'  => (string) ($i->buyer_customer_number ?: $i->contract_number ?: ''),
			],
			'seller' => self::seller($xml),
			'net'    => $net,
			'vat'    => $vat,
		];
	}

	/**
	 * The invoice line items from the flattened `xml_data` (`tetelek.tetel` - a
	 * single object for one line, an array for many). Each line: name, quantity
	 * label, net / vat / gross amounts.
	 *
	 * @param  array<string, mixed>  $xml
	 * @return array<int, array<string, mixed>>
	 */
	private static function lines(array $xml): array
	{
		$raw = $xml['tetelek']['tetel'] ?? [];
		if (! is_array($raw)) {
			return [];
		}
		// A single <tetel> flattens to one assoc array; wrap it into a list.
		if (isset($raw['termeknev']) || isset($raw['bruttoar'])) {
			$raw = [$raw];
		}

		$lines = [];
		foreach ($raw as $t) {
			if (! is_array($t)) {
				continue;
			}
			$lines[] = [
				'name'  => self::cleanName((string) ($t['termeknev'] ?? '')),
				'qty'   => trim(self::qty($t['menny'] ?? '') . ' ' . (string) ($t['mennyegys'] ?? '')),
				'net'   => self::moneyInt($t['nettoar'] ?? null),
				'vat'   => self::moneyInt($t['afaertek'] ?? null),
				'gross' => self::moneyInt($t['bruttoar'] ?? null),
			];
		}

		return $lines;
	}

	/**
	 * The invoice issuer (seller) block from `xml_data.fejlec.elado`, or null when
	 * absent (then the view falls back to the CMS company data).
	 *
	 * @param  array<string, mixed>  $xml
	 * @return array<string, string>|null
	 */
	private static function seller(array $xml): ?array
	{
		$elado = $xml['fejlec']['elado'] ?? null;
		if (! is_array($elado) || trim((string) ($elado['nev'] ?? '')) === '') {
			return null;
		}

		return [
			'name'    => trim((string) ($elado['nev'] ?? '')),
			'tax'     => trim((string) ($elado['adoszam'] ?? '')),
			'address' => self::addr($elado['cim'] ?? null),
		];
	}

	/**
	 * Assemble a one-line address from a flattened `<cim>` node (irszam telepules,
	 * kozternev kozterjell hazszam epulet emelet ajto), or '' when empty.
	 */
	private static function addr(mixed $cim): string
	{
		if (! is_array($cim)) {
			return '';
		}

		$head   = trim((string) ($cim['irszam'] ?? '') . ' ' . (string) ($cim['telepules'] ?? ''));
		$street = trim(implode(' ', array_filter([
			(string) ($cim['kozternev'] ?? ''), (string) ($cim['kozterjell'] ?? ''), (string) ($cim['hazszam'] ?? ''),
			(string) ($cim['epulet'] ?? ''), (string) ($cim['emelet'] ?? ''), (string) ($cim['ajto'] ?? ''),
		], static fn (string $v): bool => trim($v) !== '')));

		$addr = trim($head . ($head !== '' && $street !== '' ? ', ' : ' ') . $street);

		return (string) preg_replace('/\s+/', ' ', $addr);
	}

	/**
	 * The item name = the FULL original `termeknev`, with runs of 2+ spaces turned
	 * into line breaks so it renders as on the PDF (the source packs the logical
	 * lines - "szerződésszám: ...", "[..] SZJ ...", "... díjcsomag", "időszak: ..." -
	 * with multiple spaces). Rendered with `white-space: pre-line`.
	 */
	private static function cleanName(string $value): string
	{
		return (string) preg_replace('/[ \t]{2,}/', "\n", trim($value));
	}

	/**
	 * A clean quantity label: "1.00" -> "1", "1.50" -> "1,5".
	 */
	private static function qty(mixed $value): string
	{
		$value = trim((string) $value);
		if ($value === '' || ! is_numeric(str_replace(',', '.', $value))) {
			return $value;
		}

		return rtrim(rtrim(number_format((float) str_replace(',', '.', $value), 2, ',', ' '), '0'), ',');
	}

	/**
	 * Parse a money string ("6 651,75" / "6651.75") to a rounded int; 0 if empty.
	 */
	private static function moneyInt(mixed $value): int
	{
		$v = trim((string) $value);
		if ($v === '') {
			return 0;
		}
		$v = (string) preg_replace('/[^\d,.\-]/', '', $v);
		$lastComma = strrpos($v, ',');
		$lastDot   = strrpos($v, '.');
		if ($lastComma !== false && $lastDot !== false) {
			$dec      = $lastComma > $lastDot ? ',' : '.';
			$thousand = $dec === ',' ? '.' : ',';
			$v = str_replace([$thousand, $dec], ['', '.'], $v);
		} elseif ($lastComma !== false) {
			$v = preg_match('/,\d{1,2}$/', $v) ? str_replace(',', '.', $v) : str_replace(',', '', $v);
		}

		return is_numeric($v) ? (int) round((float) $v) : 0;
	}

	/**
	 * Build the settlement set + the credit-amount map for a set of invoices,
	 * keyed `payable_type|payable_id|Y-m`. One query per payable type present.
	 *
	 * @param  Collection<int, Invoice>  $invoices
	 * @return array{0: array<string, true>, 1: array<string, int>}
	 */
	private static function ledger(Collection $invoices): array
	{
		$idsByType = [];
		foreach ($invoices as $i) {
			if ($i->payable_type !== null && $i->payable_id !== null) {
				$idsByType[$i->payable_type][(int) $i->payable_id] = true;
			}
		}

		$settled = [];
		$credits = [];
		foreach ($idsByType as $type => $ids) {
			$rows = Payment::query()
				->where('payable_type', $type)
				->whereIn('payable_id', array_keys($ids))
				->whereIn('kind', [Payment::KIND_SETTLEMENT, Payment::KIND_CREDIT])
				->get();

			foreach ($rows as $p) {
				$key = $type . '|' . $p->payable_id . '|' . $p->period->format('Y-m');
				if ($p->kind === Payment::KIND_SETTLEMENT) {
					$settled[$key] = true;
				} else {
					$credits[$key] = ($credits[$key] ?? 0) + (int) $p->amount;
				}
			}
		}

		return [$settled, $credits];
	}

	/**
	 * The settlement-cell key of an invoice (`payable_type|payable_id|Y-m`), or
	 * null when the invoice is not tied to a concrete service + billing month.
	 */
	private static function cellKey(Invoice $i): ?string
	{
		if ($i->payable_type === null || $i->payable_id === null || $i->period_start === null) {
			return null;
		}

		return $i->payable_type . '|' . $i->payable_id . '|' . $i->period_start->format('Y-m');
	}

	/**
	 * Resolve one invoice OWNED by the customer (the download ownership gate), or
	 * null. Only invoices that actually have a stored PDF resolve.
	 */
	public static function find(int $invoiceId, int $customerId): ?Invoice
	{
		if ($invoiceId <= 0 || $customerId <= 0) {
			return null;
		}

		return Invoice::query()
			->where('id', $invoiceId)
			->where('customers_id', $customerId)
			->where('deleted', 0)
			->where('has_pdf', 1)
			->first();
	}

	/**
	 * The "YYYY. month" period label (from the billing period, else the issue date).
	 */
	private static function period(Invoice $i): string
	{
		$d = $i->period_start ?? $i->issue_date;
		if ($d === null) {
			return '';
		}
		return $d->format('Y') . '. ' . (self::MONTHS[(int) $d->format('n')] ?? '');
	}

	/**
	 * A short service label for the row sub-line.
	 */
	private static function service(Invoice $i): string
	{
		return match ($i->payable_type) {
			'subscription' => 'Royal Telekom',
			'lease'        => 'Bérlemény',
			default        => 'Royal Telekom',
		};
	}
}
