<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Document;
use App\Models\Invoice;
use App\Models\Subscription;
use App\Support\SiteContent;
use App\Support\WebContracts;
use App\Support\WebDocuments;
use App\Support\WebInvoices;
use App\Support\WebPackages;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
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

		// Unlinked accounts (pending contract approval) get NO customer data - the
		// dashboard then shows only the "Szerződés hozzárendelése" card; the empty
		// arrays gate the financial hero + contracts list in the view.
		return view('pages.dashboard', $this->commonContext() + [
			'invoices'  => WebInvoices::forCustomer($cid),
			'contracts' => WebContracts::forCustomer($cid),
			'bank'      => $this->bankDetails(),
		]);
	}

	/**
	 * Invoices (Számláim) - /szamlak
	 *
	 * @example GET /szamlak -> PortalController::invoices()
	 */
	public function invoices(): View|RedirectResponse
	{
		$cid = $this->currentCustomerId();
		// The Számláim menu is hidden until the account is linked; a stale/direct
		// hit while unlinked goes back to the dashboard (no mock invoices).
		if ($cid <= 0) {
			return redirect()->route('dashboard');
		}

		return view('pages.invoices', $this->commonContext() + [
			'invoices' => WebInvoices::forCustomer($cid, true), // with the full document detail for the preview modal
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
	public function plans(): View|RedirectResponse
	{
		$cid = $this->currentCustomerId();
		// The Szerződéseim menu is hidden until the account is linked; a stale/direct
		// hit while unlinked goes back to the dashboard (no mock contracts).
		if ($cid <= 0) {
			return redirect()->route('dashboard');
		}

		return view('pages.plans', $this->commonContext() + [
			'contracts' => WebContracts::forCustomer($cid),
			'plans'     => WebPackages::availablePlans($this->currentProductIds($cid)),
		]);
	}

	/**
	 * Documents (Dokumentumok) - /dokumentumok
	 *
	 * The page lists the PUBLIC document library (the shared `documents` rows
	 * bound to 'web_documents'/'portal'/'documents', uploaded in rgadmin under
	 * WEBOLDALAK -> Ügyfélkapu -> Dokumentumok), read through App\Support\
	 * WebDocuments. Same library for every signed-in customer; per-customer
	 * private documents are a later round. The intro + help cards stay CMS-driven.
	 *
	 * @example GET /dokumentumok -> PortalController::docs()
	 */
	public function docs(): View
	{
		return view('pages.docs', $this->commonContext() + [
			'docs' => WebDocuments::all(),
			'tags' => WebDocuments::tags(),
		]);
	}

	/**
	 * Download a portal library document (gated): streams only documents that
	 * belong to the published portal library - a private/foreign document id 404s.
	 * Behind auth:customer, so only signed-in, verified customers can fetch.
	 *
	 * @example GET /dokumentumok/12/letoltes -> PortalController::docDownload(12)
	 */
	public function docDownload(int $id): StreamedResponse
	{
		$document = WebDocuments::downloadable($id);
		abort_if($document === null, 404);
		abort_unless(Storage::disk(Document::DISK)->exists($document->diskPath()), 404);

		return Storage::disk(Document::DISK)->download($document->diskPath(), $document->original_name);
	}

	/**
	 * Profile & settings (Profil & beállítások) - /profil
	 *
	 * @example GET /profil -> PortalController::profile()
	 */
	public function profile(): View
	{
		$cid     = $this->currentCustomerId();
		$account = auth('customer')->user();

		// Profile fields come from the ACCOUNT (cus_users), never the CRM customer.
		return view('pages.profile', $this->commonContext() + [
			'contracts' => WebContracts::forCustomer($cid),
			'profile'   => [
				'phone'      => (string) ($account?->phone ?? ''),
				'birth_date' => optional($account?->birth_date)->format('Y-m-d') ?? '',
			],
			'notify'    => $this->notifyPrefs(),
		]);
	}

	/**
	 * Save the Profil -> Személyes adatok form to the signed-in account. Name,
	 * phone and birth date persist on cus_users; the e-mail (login id) is NOT
	 * editable here. Real POST -> redirect back to the same tab with a flash.
	 *
	 * @example POST /profil/szemelyes {last_name, first_name, phone, birth_date}
	 */
	public function profilePersonalSave(Request $request): RedirectResponse
	{
		$back = route('profile', ['tab' => 'personal']);

		$v = Validator::make($request->all(), [
			'last_name'  => ['required', 'string', 'max:100'],
			'first_name' => ['required', 'string', 'max:100'],
			'phone'      => ['required', 'string', 'max:50'],
			'birth_date' => ['nullable', 'date', 'before:today'],
		], [], [
			'last_name'  => 'Vezetéknév',
			'first_name' => 'Keresztnév',
			'phone'      => 'Telefonszám',
			'birth_date' => 'Születési dátum',
		]);

		if ($v->fails()) {
			return redirect($back)->withErrors($v)->withInput();
		}

		$data    = $v->validated();
		$account = auth('customer')->user();
		$account->name       = trim($data['last_name'] . ' ' . $data['first_name']);
		$account->phone      = $data['phone'];
		$account->birth_date = $data['birth_date'] ?: null;
		$account->save();

		return redirect($back)->with('pt_alert', [
			'variant' => 'success',
			'title'   => __('Adatok elmentve'),
			'message' => __('Személyes adatait sikeresen elmentettük.'),
		]);
	}

	/**
	 * Save the Profil -> Jelszó & biztonság form: verify the current password,
	 * then store the new one (the model's 'hashed' cast hashes it). Real POST ->
	 * redirect with a flash so the browser can offer to save the new password.
	 *
	 * @example POST /profil/jelszo {current_password, password, password_confirmation}
	 */
	public function profilePasswordSave(Request $request): RedirectResponse
	{
		$back    = route('profile', ['tab' => 'security']);
		$account = auth('customer')->user();

		$v = Validator::make($request->all(), [
			'current_password' => ['required', 'string'],
			'password'         => ['required', 'string', 'min:10', 'confirmed', 'regex:/[A-Z]/', 'regex:/[0-9]/'],
		], [
			'current_password.required' => 'Kérjük, adja meg a jelenlegi jelszavát.',
			'password.required'         => 'Kérjük, adja meg az új jelszót.',
			'password.min'              => 'A jelszónak legalább 10 karakter hosszúnak kell lennie.',
			'password.confirmed'        => 'A két megadott jelszó nem egyezik.',
			'password.regex'            => 'A jelszónak tartalmaznia kell nagybetűt és számot is.',
		]);

		$v->after(function ($validator) use ($request, $account): void {
			if (! Hash::check((string) $request->input('current_password'), (string) $account->password)) {
				$validator->errors()->add('current_password', 'A jelenlegi jelszó nem helyes.');
			}
		});

		if ($v->fails()) {
			return redirect($back)->withErrors($v);
		}

		$account->password = $request->input('password'); // the 'hashed' cast hashes it
		$account->save();

		return redirect($back)->with('pt_alert', [
			'variant' => 'success',
			'title'   => __('Jelszó módosítva'),
			'message' => __('Új jelszavát sikeresen elmentettük. A következő belépéskor már az új jelszavával lépjen be.'),
		]);
	}

	/**
	 * Save the Profil -> Értesítések notification toggles to the signed-in account
	 * (cus_users.settings JSON). Real POST -> redirect back to the same tab.
	 *
	 * @example POST /profil/ertesitesek {outage, promo}
	 */
	public function notificationsSave(Request $request): RedirectResponse
	{
		$account = auth('customer')->user();

		$settings           = (array) ($account->settings ?? []);
		$settings['notify'] = [
			'outage' => $request->boolean('outage'),
			'promo'  => $request->boolean('promo'),
		];
		$account->settings = $settings;
		$account->save();

		return redirect(route('profile', ['tab' => 'notif']))->with('pt_alert', [
			'variant' => 'success',
			'title'   => __('Adatok elmentve'),
			'message' => __('Értesítési beállításait sikeresen elmentettük.'),
		]);
	}

	/**
	 * The signed-in account's notification toggles, with the defaults (service
	 * outage on, promo off) when nothing is stored yet.
	 *
	 * @return array{outage: bool, promo: bool}
	 */
	private function notifyPrefs(): array
	{
		$settings = (array) (auth('customer')->user()?->settings ?? []);
		$notify   = (array) ($settings['notify'] ?? []);

		return [
			'outage' => (bool) ($notify['outage'] ?? true),
			'promo'  => (bool) ($notify['promo'] ?? false),
		];
	}

	/**
	 * Shared sidebar/topbar context (user identity + nav badges + linkage flag).
	 * No mock data: the identity is the signed-in cus_users account, the
	 * customer-data bits (address, overdue badge) only fill once the account is
	 * linked to a CRM customer. `linked` gates the customer-only nav + sections.
	 *
	 * @return array<string, mixed>
	 */
	private function commonContext(): array
	{
		// The signed-in portal account (cus_users) - the pages run behind
		// auth:customer, so this is always present.
		$account = auth('customer')->user();
		$name    = trim((string) ($account?->name ?? '')) ?: __('Ügyfél');

		$user = [
			'name'        => $name,
			'initials'    => $this->initials($name),
			'customerId'  => $account ? str_pad((string) $account->id, 5, '0', STR_PAD_LEFT) : '-',
			'email'       => (string) ($account?->email ?? ''),
			'memberSince' => optional($account?->created_at)->locale('hu')->translatedFormat('Y. F j.') ?: '',
			'address'     => '',
		];

		$cid    = $this->currentCustomerId();
		$badges = ['invoices' => 0];
		if ($cid > 0) {
			$customer        = Customer::find($cid);
			$user['address'] = (string) ($customer?->address ?? '');
			$badges['invoices'] = count(array_filter(
				WebInvoices::forCustomer($cid),
				static fn (array $i): bool => $i['status'] === 'overdue',
			));
		}

		return [
			'user'   => $user,
			'badges' => $badges,
			'notifs' => [],
			'linked' => $cid > 0,
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
	 * The product (package) ids of the logged-in customer's active subscriptions -
	 * used to flag the "Aktuális" cards in the available-plans showcase. Empty
	 * when the account is not yet linked.
	 *
	 * @return array<int, int>
	 */
	private function currentProductIds(int $customerId): array
	{
		if ($customerId <= 0) {
			return [];
		}

		return Subscription::forCustomer($customerId)
			->with('products:id')
			->get()
			->flatMap(static fn (Subscription $s): array => $s->products->pluck('id')->all())
			->map(static fn ($id): int => (int) $id)
			->unique()
			->values()
			->all();
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
	 * web_sections global.bank.*); they stay EMPTY until filled (no fake account
	 * number is ever shown). The Közlemény reference is the linked customer's
	 * customer number (the per-invoice "Kifizetem" overrides it with the concrete
	 * invoice number in the view).
	 *
	 * @return array<string, string>
	 * @example PortalController::bankDetails()['iban'] // 'HU42 1177 ...' (once set)
	 */
	private function bankDetails(): array
	{
		$cms = app(SiteContent::class);

		$ref = '';
		$cid = $this->currentCustomerId();
		if ($cid > 0) {
			$number = Customer::where('id', $cid)->value('customer_number');
			if ($number) {
				$ref = (string) $number;
			}
		}

		return [
			'name'    => $cms->get('global.bank.name')    ?: 'Royal Telekom Kft.',
			'bank'    => $cms->get('global.bank.bank'),
			'account' => $cms->get('global.bank.account'),
			'iban'    => $cms->get('global.bank.iban'),
			'swift'   => $cms->get('global.bank.swift'),
			'ref'     => $ref,
		];
	}
}
