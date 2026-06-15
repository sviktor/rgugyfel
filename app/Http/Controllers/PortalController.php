<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Support\PortalMockData;
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
			'tickets'   => PortalMockData::tickets(),
			'bank'      => PortalMockData::bankDetails(),
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
			'bank'     => PortalMockData::bankDetails(),
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
	 * Usage / speed (Forgalom & sebesség) - /forgalom  [stub by design]
	 *
	 * @example GET /forgalom -> PortalController::usage()
	 */
	public function usage(): View
	{
		return view('pages.stub', $this->commonContext() + [
			'title' => 'Forgalom és sebességmérés',
			'body'  => 'Itt fogja látni az aktuális hónap adatforgalmát, a sebességmérési eredményeit és a hálózati statisztikákat. A funkció hamarosan elérhető.',
		]);
	}

	/**
	 * Support / tickets (Hibabejelentés) - /hibabejelentes
	 *
	 * @example GET /hibabejelentes -> PortalController::tickets()
	 */
	public function tickets(): View
	{
		return view('pages.tickets', $this->commonContext() + [
			'tickets' => PortalMockData::tickets(),
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
}
