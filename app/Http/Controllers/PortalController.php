<?php

namespace App\Http\Controllers;

use App\Support\PortalMockData;
use Illuminate\View\View;

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
		return view('pages.dashboard', $this->commonContext() + [
			'invoices'  => PortalMockData::invoices(),
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
		return view('pages.invoices', $this->commonContext() + [
			'invoices' => PortalMockData::invoices(),
			'bank'     => PortalMockData::bankDetails(),
		]);
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
		return [
			'user'   => PortalMockData::user(),
			'badges' => PortalMockData::badges(),
			'notifs' => PortalMockData::notifications(),
		];
	}
}
