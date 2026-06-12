<?php

namespace App\Http\Controllers;

use App\Support\PortalMockData;
use Illuminate\View\View;

/**
 * Portal pages controller - dashboard + 6 stub sections.
 *
 * The dashboard renders a focused 3-card overview from mock data.
 * The other six (invoices, plans, usage, tickets, docs, profile) ship
 * as "coming soon" stubs for now - the full UIs live in the design's
 * portal-{dashboard,plans-tickets,profile,docs}.jsx files and will be
 * ported once we have the real `customers` / `invoices` / `subscriptions`
 * / `cp_tickets` schemas (rgadmin migrations + cp_tickets migration here).
 */
class PortalController extends Controller
{
	/**
	 * Dashboard - /
	 *
	 * @example  GET /  →  PortalController::dashboard()
	 */
	public function dashboard(): View
	{
		return view('pages.dashboard', $this->commonContext() + [
			'invoices'  => PortalMockData::invoices(),
			'contracts' => PortalMockData::contracts(),
			'tickets'   => PortalMockData::tickets(),
		]);
	}

	public function invoices(): View
	{
		return view('pages.stub', $this->commonContext() + [
			'title' => 'Számláim',
			'body'  => 'A számlatörténet, PDF-letöltés, fizetési átutalási QR kód, banki utalás-segéd, csekk és online fizetés. Hamarosan elérhető.',
		]);
	}

	public function plans(): View
	{
		return view('pages.stub', $this->commonContext() + [
			'title' => 'Szerződéseim',
			'body'  => 'Aktív internet/TV/mobil előfizetések, csomagváltás, hűségidő-státusz, új szerződés igénylés. Hamarosan elérhető.',
		]);
	}

	public function usage(): View
	{
		return view('pages.stub', $this->commonContext() + [
			'title' => 'Forgalom és sebességmérés',
			'body'  => 'Az aktuális hónap adatforgalma, sebességmérési eredmények, hálózati statisztikák. Hamarosan elérhető.',
		]);
	}

	public function tickets(): View
	{
		return view('pages.stub', $this->commonContext() + [
			'title' => 'Hibabejelentés',
			'body'  => 'Aktív és lezárt hibajegyek, új bejelentés, üzenetváltás az ügyintézővel. Hamarosan elérhető.',
		]);
	}

	public function docs(): View
	{
		return view('pages.stub', $this->commonContext() + [
			'title' => 'Dokumentumok',
			'body'  => 'Szerződésmásolatok, ÁSZF, formanyomtatványok és személyes dokumentumok letöltése. Hamarosan elérhető.',
		]);
	}

	public function profile(): View
	{
		return view('pages.stub', $this->commonContext() + [
			'title' => 'Profil és beállítások',
			'body'  => 'Személyes adatok módosítása, jelszóváltás, értesítési beállítások, kapcsolatfelvételi módok. Hamarosan elérhető.',
		]);
	}

	/**
	 * Shared sidebar/topbar context (user + badges).
	 *
	 * @return array<string, mixed>
	 */
	private function commonContext(): array
	{
		return [
			'user'   => PortalMockData::user(),
			'badges' => PortalMockData::badges(),
		];
	}
}
