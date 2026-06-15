<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Support\PortalMockData;
use App\Support\SiteContent;
use App\Support\WebInvoices;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Portal pages controller - the authenticated customer area.
 *
 * Ports the Royal Telecom Design System portal kit
 * (w:\sv\rg\_design\royal-telecom-sites\project\portal-*.jsx). Visual
 * mockup only: data comes from App\Support\PortalMockData and is replaced
 * once the real `customers` / `invoices` / `subscriptions` / `cp_tickets`
 * schemas land (rgadmin migrations + this project's cp_* migrations).
 */
class PortalController extends Controller
{
	/**
	 * Dashboard (Főoldal) - /
	 *
	 * @example GET / -> PortalController::dashboard()
	 */
	public function dashboard(): View
	{
		$cid = $this->currentCustomerId();

		return view('pages.dashboard', $this->commonContext() + [
			'invoices'  => $cid > 0 ? WebInvoices::forCustomer($cid) : PortalMockData::invoices(),
			'contracts' => PortalMockData::contracts(),
			'bank'      => $this->bankDetails(),
		]);
	}

	/**
	 * Invoices (Számláim) - /szamlak
	 *
	 * @example GET /szamlak -> PortalController::invoices()
	 */
	public function invoices(): View
	{
		$cid = $this->currentCustomerId();

		return view('pages.invoices', $this->commonContext() + [
			'invoices' => $cid > 0 ? WebInvoices::forCustomer($cid) : PortalMockData::invoices(),
			'bank'     => $this->bankDetails(),
		]);
	}

	/**
	 * Download one of the logged-in customer's own invoice PDFs (ownership-gated).
	 * A foreign / unknown / PDF-less id 404s.
	 *
	 * @example GET /szamlak/12/letoltes -> PortalController::invoiceDownload(12)
	 */
	public function invoiceDownload(int $id): StreamedResponse
	{
		$invoice = WebInvoices::find($id, $this->currentCustomerId());
		abort_unless($invoice !== null && (string) $invoice->pdf_path !== '' && Storage::disk(Invoice::DISK)->exists($invoice->pdf_path), 404);

		$name = preg_replace('/[^\w\-.]+/', '_', (string) ($invoice->invoice_number ?: ('szamla-' . $invoice->id))) . '.pdf';

		return Storage::disk(Invoice::DISK)->download($invoice->pdf_path, $name);
	}

	/**
	 * Subscriptions / plans (Szerződéseim) - /szerzodeseim
	 *
	 * @example GET /szerzodeseim -> PortalController::plans()
	 */
	public function plans(): View
	{
		return view('pages.plans', $this->commonContext() + [
			'contracts' => PortalMockData::contracts(),
			'plans'     => PortalMockData::availablePlans(),
		]);
	}

	/**
	 * Documents (Dokumentumok) - /dokumentumok
	 *
	 * @example GET /dokumentumok -> PortalController::docs()
	 */
	public function docs(): View
	{
		return view('pages.docs', $this->commonContext() + [
			'docs' => PortalMockData::docs(),
		]);
	}

	/**
	 * Profile & settings (Profil & beállítások) - /profil
	 *
	 * @example GET /profil -> PortalController::profile()
	 */
	public function profile(): View
	{
		return view('pages.profile', $this->commonContext() + [
			'contracts' => PortalMockData::contracts(),
		]);
	}

	/**
	 * Shared sidebar/topbar context (user + nav badges + notifications).
	 *
	 * @return array<string, mixed>
	 */
	private function commonContext(): array
	{
		$badges = PortalMockData::badges();

		// The signed-in portal account (cus_users) - the pages run behind
		// auth:customer, so this is always present. Name/monogram/identifier come
		// from the account; address + member-since stay demo data until invoices
		// bind in a later round.
		$account = auth('customer')->user();
		$user    = PortalMockData::user();
		if ($account) {
			$name               = trim((string) $account->name);
			$user['name']       = $name !== '' ? $name : $user['name'];
			$user['initials']   = $this->initials($user['name']);
			$user['customerId'] = str_pad((string) $account->id, 5, '0', STR_PAD_LEFT);
			$user['email']      = (string) $account->email;
			$user['memberSince'] = optional($account->created_at)->locale('hu')->translatedFormat('Y. F j.') ?: $user['memberSince'];
		}

		// Real overdue-invoice badge once the account is linked to a CRM customer.
		$cid = $this->currentCustomerId();
		if ($cid > 0) {
			$badges['invoices'] = count(array_filter(
				WebInvoices::forCustomer($cid),
				static fn (array $i): bool => $i['status'] === 'overdue',
			));
		}

		return [
			'user'   => PortalMockData::user(),
			'badges' => $badges,
			'notifs' => PortalMockData::notifications(),
		];
	}

	/**
	 * The logged-in customer's CRM `customers.id`, or 0 when the account is not
	 * yet linked to a customer (pending contract approval) - in which case the
	 * pages fall back to the demo data.
	 */
	private function currentCustomerId(): int
	{
		return (int) (auth('customer')->user()?->customers_id ?? 0);
	}

	/**
	 * Monogram for the sidebar avatar: the initials of the first two name words,
	 * uppercased. Multibyte-safe (Hungarian accents, e.g. "Kis Éva" -> "KÉ").
	 *
	 * @example PortalController::initials('Kis Éva') // 'KÉ'
	 */
	private function initials(string $name): string
	{
		$words = preg_split('/\s+/', trim($name), -1, PREG_SPLIT_NO_EMPTY) ?: [];
		$out   = mb_substr($words[0] ?? '', 0, 1);
		if (isset($words[1])) {
			$out .= mb_substr($words[1], 0, 1);
		}

		return mb_strtoupper($out);
	}

	/**
	 * Bank-transfer details for the payment modal. The account fields are
	 * operator-editable in rgadmin (WEBOLDALAK -> Ügyfélkapu -> Beállítások ->
	 * web_sections global.bank.*); PortalMockData::bankDetails() supplies the
	 * fallback values (used before the editor is first opened) and the
	 * per-customer Közlemény reference (still demo data until invoices bind).
	 *
	 * @return array<string, string>
	 * @example PortalController::bankDetails()['iban'] // 'HU42 1177 ...'
	 */
	private function bankDetails(): array
	{
		$cms  = app(SiteContent::class);
		$mock = PortalMockData::bankDetails();

		return [
			'name'    => $cms->get('global.bank.name')    ?: $mock['name'],
			'bank'    => $cms->get('global.bank.bank')    ?: $mock['bank'],
			'account' => $cms->get('global.bank.account') ?: $mock['account'],
			'iban'    => $cms->get('global.bank.iban')    ?: $mock['iban'],
			'swift'   => $cms->get('global.bank.swift')   ?: $mock['swift'],
			'ref'     => $mock['ref'],
		];
	}
}
