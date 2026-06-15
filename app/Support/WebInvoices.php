<?php

namespace App\Support;

use App\Models\Invoice;

/**
 * Read layer for the customer-portal invoices page: maps the shared `invoices`
 * rows (written by rgadmin's `invoices:sync`) into the view shape the existing
 * `pages.invoices` Blade expects (the {@see PortalMockData::invoices()} shape),
 * plus a real PDF download URL.
 *
 * Always scoped to ONE customer (the authenticated `customers_id`); the model's
 * `forCustomer` scope is the single ownership filter.
 *
 * @example
 *   $rows = WebInvoices::forCustomer($user->customers_id);
 *   $invoice = WebInvoices::find($id, $user->customers_id);   // ownership-checked
 */
class WebInvoices
{
	/** Hungarian month names (1-based). */
	private const MONTHS = [
		1 => 'január', 2 => 'február', 3 => 'március', 4 => 'április', 5 => 'május', 6 => 'június',
		7 => 'július', 8 => 'augusztus', 9 => 'szeptember', 10 => 'október', 11 => 'november', 12 => 'december',
	];

	/**
	 * The customer's invoices as view rows (newest first).
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function forCustomer(int $customerId): array
	{
		if ($customerId <= 0) {
			return [];
		}

		return Invoice::forCustomer($customerId)->get()->map(static function (Invoice $i): array {
			$due  = $i->due_date;
			$paid = (bool) $i->paid;

			$status = $paid
				? 'paid'
				: ($due !== null && $due->isPast() ? 'overdue' : 'pending');

			return [
				'id'          => (string) ($i->invoice_number ?: ('SZ-' . $i->id)),
				'period'      => self::period($i),
				'service'     => self::service($i),
				'issued'      => optional($i->issue_date)->format('Y-m-d') ?? '',
				'due'         => optional($i->due_date)->format('Y-m-d') ?? '',
				'amount'      => (int) round((float) $i->gross),
				'status'      => $status,
				'lines'       => [], // line items are not parsed - the real PDF is downloadable
				'downloadUrl' => $i->has_pdf ? route('invoices.download', ['id' => $i->id]) : null,
			];
		})->all();
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
