<?php

namespace App\Support;

/**
 * Royal Telekom Portal - mock customer data.
 *
 * Mirrors the constants in
 *   w:\sv\rg\_design\royal-telecom-sites\project\portal-shared.jsx
 * (PORTAL_USER, PORTAL_INVOICES, PORTAL_CONTRACTS, PORTAL_TICKETS).
 *
 * Replaced once the `customers`, `invoices`, `subscriptions`, `tickets`
 * tables land (owned by rgadmin + this project's `cp_tickets`).
 */
final class PortalMockData
{
	/**
	 * Currently "logged in" mock user.
	 *
	 * @return array<string, mixed>
	 */
	public static function user(): array
	{
		return [
			'name'        => 'Kis Éva',
			'email'       => 'kis.eva@example.hu',
			'customerId'  => '2024-0382',
			'initials'    => 'KÉ',
			'address'     => '1037 Budapest, Aranyhegyi út 14. III/12.',
			'memberSince' => '2019-03-15',
		];
	}

	/**
	 * Open badge counts shown next to nav items.
	 *
	 * @return array<string, int>
	 */
	public static function badges(): array
	{
		return [
			'invoices' => \count(\array_filter(self::invoices(), fn ($i) => $i['status'] === 'overdue')),
			'tickets'  => \count(\array_filter(self::tickets(),  fn ($t) => $t['status'] === 'open')),
		];
	}

	/**
	 * Invoices - most recent first.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function invoices(): array
	{
		return [
			['id' => 'RT-2026-04-1827', 'period' => '2026. április', 'service' => 'Royal Fiber 1000 + Royal TV Premium', 'issued' => '2026-04-01', 'due' => '2026-04-15', 'amount' => 18990, 'status' => 'overdue'],
			['id' => 'RT-2026-05-2104', 'period' => '2026. május',   'service' => 'Royal Fiber 1000 + Royal TV Premium', 'issued' => '2026-05-01', 'due' => '2026-05-15', 'amount' => 17990, 'status' => 'pending'],
			['id' => 'RT-2026-03-1556', 'period' => '2026. március', 'service' => 'Royal Fiber 1000 + Royal TV Premium', 'issued' => '2026-03-01', 'due' => '2026-03-15', 'amount' => 17990, 'status' => 'paid'],
			['id' => 'RT-2026-02-1284', 'period' => '2026. február', 'service' => 'Royal Fiber 1000 + Royal TV Premium', 'issued' => '2026-02-01', 'due' => '2026-02-15', 'amount' => 17990, 'status' => 'paid'],
			['id' => 'RT-2026-01-1011', 'period' => '2026. január',  'service' => 'Royal Fiber 1000 + Royal TV Premium', 'issued' => '2026-01-01', 'due' => '2026-01-15', 'amount' => 17990, 'status' => 'paid'],
		];
	}

	/**
	 * Active service contracts (internet / TV / mobile).
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function contracts(): array
	{
		return [
			['id' => 'RT-INT-2024-0382', 'kind' => 'internet', 'icon' => 'wifi',       'name' => 'Royal Fiber 1000',    'address' => '1037 Budapest, Aranyhegyi út 14. III/12.', 'monthly' => 9990, 'status' => 'active'],
			['id' => 'RT-TV-2024-0382',  'kind' => 'tv',       'icon' => 'tv',         'name' => 'Royal TV Premium',    'address' => '1037 Budapest, Aranyhegyi út 14. III/12.', 'monthly' => 6500, 'status' => 'active'],
			['id' => 'RT-MOB-2025-1142', 'kind' => 'mobile',   'icon' => 'smartphone', 'name' => 'Royal Mobile Premium', 'address' => '+36 30 555 0382',                          'monthly' => 5990, 'status' => 'active'],
		];
	}

	/**
	 * Open & past support tickets.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function tickets(): array
	{
		return [
			['id' => '2026-0814', 'category' => 'Internet',  'subject' => 'Lassú internet a délutáni órákban',     'priority' => 'high',   'status' => 'open',   'opened' => '2026-05-02', 'assignee' => 'Vadász Gábor'],
			['id' => '2026-0789', 'category' => 'Számlázás', 'subject' => 'Hibás tételt látok az áprilisi számlán', 'priority' => 'normal', 'status' => 'open',   'opened' => '2026-04-28', 'assignee' => 'Lengyel Borbála'],
			['id' => '2026-0612', 'category' => 'TV',        'subject' => 'HBO Max nem működik',                    'priority' => 'normal', 'status' => 'closed', 'opened' => '2026-03-12', 'assignee' => 'Vadász Gábor'],
		];
	}
}
