<?php

namespace App\Support;

/**
 * Royal Telekom Portal - mock customer data + view helpers.
 *
 * Faithful mirror of the design handoff data
 *   w:\sv\rg\_design\royal-telecom-sites\project\portal-shared.jsx
 * (PORTAL_USER, BANK_DETAILS, PORTAL_INVOICES, PORTAL_CONTRACTS,
 *  AVAILABLE_PLANS, PORTAL_TICKETS) + the notifications from portal-app.jsx.
 *
 * Visual mockup only: a single file, easy to delete once the real
 * `customers` / `invoices` / `subscriptions` / `cp_tickets` tables land.
 * Amounts are integers; dates are ISO strings formatted via date()/daysUntil().
 */
final class PortalMockData
{
	/** The mock "today" - matches the design's TODAY so relative dates line up. */
	private const TODAY = '2026-05-05';

	/* ---------------------------------------------------------
	   View helpers (mirror portal-shared.jsx formatHUF/formatDate/...)
	   --------------------------------------------------------- */

	/**
	 * Format an integer HUF amount with space thousands + " Ft".
	 *
	 * @example PortalMockData::huf(18990) // '18 990 Ft'
	 */
	public static function huf(?int $n): string
	{
		if ($n === null) {
			return '';
		}

		return number_format($n, 0, ',', ' ') . ' Ft';
	}

	/**
	 * Format an ISO date as the Hungarian abbreviated form.
	 *
	 * @example PortalMockData::date('2026-04-15') // '2026. ápr. 15.'
	 */
	public static function date(?string $iso): string
	{
		if (! $iso) {
			return '';
		}

		$months = ['jan.', 'febr.', 'márc.', 'ápr.', 'máj.', 'jún.', 'júl.', 'aug.', 'szept.', 'okt.', 'nov.', 'dec.'];
		[$y, $m, $d] = array_map('intval', explode('-', substr($iso, 0, 10)));

		return $y . '. ' . ($months[$m - 1] ?? '') . ' ' . $d . '.';
	}

	/** Whole days from TODAY until $iso (negative if past). */
	public static function daysUntil(string $iso): int
	{
		return (int) round((strtotime($iso) - strtotime(self::TODAY)) / 86400);
	}

	/** Whole days from $iso ago to TODAY. */
	public static function daysAgo(string $iso): int
	{
		return (int) round((strtotime(self::TODAY) - strtotime($iso)) / 86400);
	}

	/* ---------------------------------------------------------
	   Mock data
	   --------------------------------------------------------- */

	/**
	 * Currently "logged in" mock user.
	 *
	 * @return array<string, mixed>
	 * @example PortalMockData::user()['name'] // 'Kis Éva'
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
	 * Bank-transfer details (payment popover).
	 *
	 * @return array<string, string>
	 */
	public static function bankDetails(): array
	{
		return [
			'name'    => 'Royal Telekom Kft.',
			'iban'    => 'HU42 1177 3329 0000 1234 5678 0012',
			'account' => '11773329-12345678-00120000',
			'swift'   => 'OTPVHUHB',
			'bank'    => 'OTP Bank Nyrt.',
			'ref'     => '2024-0382',
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
	 * Invoices (with line items on the unpaid ones) - newest first.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function invoices(): array
	{
		return [
			[
				'id' => 'RT-2026-04-1827', 'period' => '2026. április', 'service' => 'Royal Fiber 1000 + Royal TV Premium',
				'issued' => '2026-04-01', 'due' => '2026-04-15', 'amount' => 18990, 'status' => 'overdue',
				'lines' => [
					['name' => 'Royal Fiber 1000', 'note' => 'Internet csomag · havi díj', 'qty' => '1 hó', 'unit' => 9990, 'total' => 9990],
					['name' => 'Royal TV Premium', 'note' => '180+ csatorna, HBO Max', 'qty' => '1 hó', 'unit' => 6500, 'total' => 6500],
					['name' => 'Set-top box bérlet', 'note' => '4K UHD, hangvezérlés', 'qty' => '1 hó', 'unit' => 1500, 'total' => 1500],
					['name' => 'Késedelmi díj', 'note' => 'Lejárat: 2026.04.15.', 'qty' => '1 db', 'unit' => 1000, 'total' => 1000],
				],
			],
			[
				'id' => 'RT-2026-05-2104', 'period' => '2026. május', 'service' => 'Royal Fiber 1000 + Royal TV Premium',
				'issued' => '2026-05-01', 'due' => '2026-05-15', 'amount' => 17990, 'status' => 'pending',
				'lines' => [
					['name' => 'Royal Fiber 1000', 'note' => null, 'qty' => '1 hó', 'unit' => 9990, 'total' => 9990],
					['name' => 'Royal TV Premium', 'note' => null, 'qty' => '1 hó', 'unit' => 6500, 'total' => 6500],
					['name' => 'Set-top box bérlet', 'note' => null, 'qty' => '1 hó', 'unit' => 1500, 'total' => 1500],
				],
			],
			['id' => 'RT-2026-03-1556', 'period' => '2026. március', 'service' => 'Royal Fiber 1000 + Royal TV Premium', 'issued' => '2026-03-01', 'due' => '2026-03-15', 'amount' => 17990, 'status' => 'paid', 'lines' => []],
			['id' => 'RT-2026-02-1284', 'period' => '2026. február', 'service' => 'Royal Fiber 1000 + Royal TV Premium', 'issued' => '2026-02-01', 'due' => '2026-02-15', 'amount' => 17990, 'status' => 'paid', 'lines' => []],
			['id' => 'RT-2026-01-1011', 'period' => '2026. január', 'service' => 'Royal Fiber 1000 + Royal TV Premium', 'issued' => '2026-01-01', 'due' => '2026-01-15', 'amount' => 17990, 'status' => 'paid', 'lines' => []],
			['id' => 'RT-2025-12-0742', 'period' => '2025. december', 'service' => 'Royal Fiber 500 + Royal TV Premium', 'issued' => '2025-12-01', 'due' => '2025-12-15', 'amount' => 15990, 'status' => 'paid', 'lines' => []],
			['id' => 'RT-2025-11-0489', 'period' => '2025. november', 'service' => 'Royal Fiber 500 + Royal TV Premium', 'issued' => '2025-11-01', 'due' => '2025-11-15', 'amount' => 15990, 'status' => 'paid', 'lines' => []],
			['id' => 'RT-2025-10-0218', 'period' => '2025. október', 'service' => 'Royal Fiber 500 + Royal TV Premium', 'issued' => '2025-10-01', 'due' => '2025-10-15', 'amount' => 15990, 'status' => 'paid', 'lines' => []],
		];
	}

	/**
	 * Active service contracts (internet / TV / mobile) with details.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function contracts(): array
	{
		return [
			[
				'id' => 'RT-INT-2024-0382', 'kind' => 'internet', 'kindLabel' => 'Internet', 'icon' => 'wifi',
				'name' => 'Royal Fiber 1000', 'address' => '1037 Budapest, Aranyhegyi út 14. III/12.',
				'monthly' => 9990, 'status' => 'active',
				'summary' => ['1000/300 Mbps', 'Wi-Fi 6 router', 'Statikus IP'],
				'metrics' => [
					['label' => 'Letöltés', 'value' => '1000 Mbps'],
					['label' => 'Feltöltés', 'value' => '300 Mbps'],
					['label' => 'Aktiválva', 'value' => '2024.03.10.'],
					['label' => 'Eszköz', 'value' => 'Royal Box Pro'],
				],
				'loyalty' => ['percent' => 73, 'end' => '2026-09-15', 'alert' => null],
			],
			[
				'id' => 'RT-TV-2024-0382', 'kind' => 'tv', 'kindLabel' => 'Televízió', 'icon' => 'tv',
				'name' => 'Royal TV Premium', 'address' => '1037 Budapest, Aranyhegyi út 14. III/12.',
				'monthly' => 6500, 'status' => 'active',
				'summary' => ['180+ csatorna', 'HBO Max', '4K UHD'],
				'metrics' => [
					['label' => 'Csatornák', 'value' => '180+'],
					['label' => 'Streaming', 'value' => 'HBO Max + Max Sport'],
					['label' => 'Aktiválva', 'value' => '2024.03.10.'],
					['label' => 'Eszköz', 'value' => 'Royal STB 4K'],
				],
				'loyalty' => ['percent' => 73, 'end' => '2026-09-15', 'alert' => null],
			],
			[
				'id' => 'RT-MOB-2025-1142', 'kind' => 'mobile', 'kindLabel' => 'Mobil', 'icon' => 'smartphone',
				'name' => 'Royal Mobile Premium', 'address' => '+36 30 555 0382 · MNP-átvitel',
				'monthly' => 5990, 'status' => 'active',
				'summary' => ['Korlátlan adat', '5G+', 'EU roaming'],
				'metrics' => [
					['label' => 'Adatforgalom', 'value' => 'Korlátlan'],
					['label' => 'Hálózat', 'value' => '5G+'],
					['label' => 'Roaming', 'value' => 'EU benne'],
					['label' => 'Aktiválva', 'value' => '2025.06.04.'],
				],
				'loyalty' => ['percent' => 33, 'end' => '2027-06-04', 'alert' => null],
			],
		];
	}

	/**
	 * Available plans for the plan-switch flow, grouped by kind.
	 *
	 * @return array<string, array<int, array<string, mixed>>>
	 */
	public static function availablePlans(): array
	{
		return [
			'internet' => [
				['id' => 'fiber-500', 'kind' => 'Internet', 'name' => 'Royal Fiber 500', 'heroNum' => '500', 'heroUnit' => 'Mbps', 'monthly' => 7990, 'current' => false, 'featured' => false, 'features' => ['500/200 Mbps', 'Wi-Fi 6 router benne', 'Vagyonbiztosítás']],
				['id' => 'fiber-1000', 'kind' => 'Internet', 'name' => 'Royal Fiber 1000', 'heroNum' => '1000', 'heroUnit' => 'Mbps', 'monthly' => 9990, 'current' => true, 'featured' => false, 'features' => ['1000/300 Mbps', 'Wi-Fi 6 router', 'Statikus IP']],
				['id' => 'fiber-2500', 'kind' => 'Internet', 'name' => 'Royal Fiber 2500', 'heroNum' => '2.5', 'heroUnit' => 'Gbps', 'monthly' => 14990, 'current' => false, 'featured' => true, 'features' => ['2500/1000 Mbps', 'Mesh-rendszer 3 ponttal', 'Prioritásos ügyfélszolgálat']],
			],
			'tv' => [
				['id' => 'tv-basic', 'kind' => 'TV', 'name' => 'Royal TV Alap', 'heroNum' => '90+', 'heroUnit' => 'csatorna', 'monthly' => 3500, 'current' => false, 'featured' => false, 'features' => ['90+ csatorna', 'HD adás', 'Time-shift 7 nap']],
				['id' => 'tv-premium', 'kind' => 'TV', 'name' => 'Royal TV Premium', 'heroNum' => '180+', 'heroUnit' => 'csatorna', 'monthly' => 6500, 'current' => true, 'featured' => false, 'features' => ['180+ csatorna', 'HBO Max', '4K UHD']],
				['id' => 'tv-cinema', 'kind' => 'TV', 'name' => 'Royal TV Cinema', 'heroNum' => '230+', 'heroUnit' => 'csatorna', 'monthly' => 8990, 'current' => false, 'featured' => true, 'features' => ['230+ csatorna', 'HBO Max + Max Sport', 'Disney+ benne', 'Multi-room (2 STB)']],
			],
			'mobile' => [
				['id' => 'mob-light', 'kind' => 'Mobil', 'name' => 'Royal Mobile Light', 'heroNum' => '20', 'heroUnit' => 'GB', 'monthly' => 3490, 'current' => false, 'featured' => false, 'features' => ['20 GB adat', 'Korlátlan beszélgetés', '5G hálózat']],
				['id' => 'mob-premium', 'kind' => 'Mobil', 'name' => 'Royal Mobile Premium', 'heroNum' => '∞', 'heroUnit' => 'adat', 'monthly' => 5990, 'current' => true, 'featured' => false, 'features' => ['Korlátlan adat', '5G+', 'EU roaming']],
				['id' => 'mob-vip', 'kind' => 'Mobil', 'name' => 'Royal Mobile VIP', 'heroNum' => '∞', 'heroUnit' => 'mindenhol', 'monthly' => 8990, 'current' => false, 'featured' => true, 'features' => ['Korlátlan adat világszerte', '5G+ prioritás', 'eSIM 3 eszközre']],
			],
		];
	}

	/**
	 * Support tickets with full message threads.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function tickets(): array
	{
		return [
			[
				'id' => '2026-0814', 'category' => 'Internet', 'subject' => 'Lassú internet a délutáni órákban',
				'priority' => 'high', 'status' => 'open', 'opened' => '2026-05-02', 'closed' => null, 'assignee' => 'Vadász Gábor',
				'lastMessage' => 'Megérkezett a hibajegy, a szakembereink most vizsgálják meg a szolgáltatási területet.',
				'messages' => [
					['from' => 'customer', 'at' => '2026-05-02', 'time' => '14:23', 'body' => 'Az utóbbi héten délutánonként (kb. 17:00-20:00 között) drasztikusan lelassul a netem. Sebességmérőn 50 Mbps körül, pedig 1000 Mbps-os a csomag. Router újraindítás nem segít.'],
					['from' => 'staff', 'author' => 'Royal Telekom · Vadász Gábor', 'at' => '2026-05-02', 'time' => '15:08', 'body' => 'Tisztelt Kis Éva! Köszönjük a bejelentést. Megnéztem a vonalát, valóban túlterhelést látunk a III. kerületi csomópontban. Ma este 22:00 után konfigurációs frissítést végzünk a régióban.'],
					['from' => 'staff', 'author' => 'Royal Telekom · Vadász Gábor', 'at' => '2026-05-03', 'time' => '09:45', 'body' => 'A frissítés sikeres volt. Kérem, mérje meg ma délután a sebességet és jelezze, ha továbbra is fennáll a probléma. Köszönjük türelmét!'],
				],
			],
			[
				'id' => '2026-0789', 'category' => 'Számlázás', 'subject' => 'Hibás tételt látok az áprilisi számlán',
				'priority' => 'normal', 'status' => 'open', 'opened' => '2026-04-28', 'closed' => null, 'assignee' => 'Lengyel Borbála',
				'lastMessage' => 'Köszönjük, már továbbítottam a számlázásra, 48 órán belül visszajelzünk.',
				'messages' => [
					['from' => 'customer', 'at' => '2026-04-28', 'time' => '10:12', 'body' => 'Az áprilisi számlán szerepel egy 1500 Ft-os "Set-top box bérlet" tétel, de én már januárban jeleztem, hogy nem használom a STB-t és visszaküldtem.'],
					['from' => 'staff', 'author' => 'Royal Telekom · Lengyel Borbála', 'at' => '2026-04-28', 'time' => '11:30', 'body' => 'Köszönjük, már továbbítottam a számlázásra, 48 órán belül visszajelzünk a korrekcióval kapcsolatban.'],
				],
			],
			[
				'id' => '2026-0612', 'category' => 'TV', 'subject' => 'HBO Max nem működik', 'priority' => 'normal', 'status' => 'closed',
				'opened' => '2026-03-12', 'closed' => '2026-03-13', 'assignee' => 'Vadász Gábor',
				'lastMessage' => 'Megoldva - új aktiváló kód kiküldve, a szolgáltatás újra elérhető.',
				'messages' => [
					['from' => 'customer', 'at' => '2026-03-12', 'time' => '20:15', 'body' => 'A HBO Max ma este nem indul el a STB-n, hibakód: HBO-403.'],
					['from' => 'staff', 'author' => 'Royal Telekom · Vadász Gábor', 'at' => '2026-03-13', 'time' => '09:22', 'body' => 'Új aktiváló kódot küldtem e-mailben. Indítsa újra a STB-t és kövesse a levélben szereplő utasításokat.'],
					['from' => 'customer', 'at' => '2026-03-13', 'time' => '14:08', 'body' => 'Köszönöm, működik!'],
				],
			],
			[
				'id' => '2025-1834', 'category' => 'Internet', 'subject' => 'Router cseréje WiFi 6-os modellre', 'priority' => 'low', 'status' => 'closed',
				'opened' => '2025-11-04', 'closed' => '2025-11-08', 'assignee' => 'Lengyel Borbála',
				'lastMessage' => 'Új Royal Box Pro kiszállítva és telepítve. Köszönjük együttműködését!',
				'messages' => [
					['from' => 'customer', 'at' => '2025-11-04', 'time' => '16:30', 'body' => 'Szeretnék WiFi 6-os routerre váltani.'],
					['from' => 'staff', 'author' => 'Royal Telekom · Lengyel Borbála', 'at' => '2025-11-05', 'time' => '08:15', 'body' => 'A csere ingyenes a Royal Fiber 1000-es csomag ügyfeleinek. Időpontot ajánlunk: november 8., péntek délelőtt.'],
					['from' => 'staff', 'author' => 'Royal Telekom · Lengyel Borbála', 'at' => '2025-11-08', 'time' => '11:45', 'body' => 'Új Royal Box Pro kiszállítva és telepítve. Köszönjük együttműködését!'],
				],
			],
		];
	}

	/**
	 * Topbar notification feed (mirrors portal-app.jsx PORTAL_NOTIFS).
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function notifications(): array
	{
		return [
			['id' => 'n1', 'icon' => 'receipt', 'tone' => 'danger', 'title' => 'Lejárt számla', 'body' => 'Az RT-2026-0412 számla fizetési határideje lejárt. Kérjük, rendezze mielőbb.', 'time' => 'Ma · 08:14', 'route' => 'invoices', 'read' => false],
			['id' => 'n3', 'icon' => 'receipt', 'tone' => 'gold', 'title' => 'Új számla érkezett', 'body' => 'Kiállítottuk május havi számláját. Esedékesség: május 15.', 'time' => 'Tegnap · 16:40', 'route' => 'invoices', 'read' => false],
			['id' => 'n4', 'icon' => 'wifi', 'tone' => 'neutral', 'title' => 'Tervezett karbantartás', 'body' => 'Május 12-én 02:00-04:00 között rövid szünet várható a szolgáltatásban.', 'time' => 'Tegnap · 11:20', 'route' => 'dashboard', 'read' => false],
			['id' => 'n5', 'icon' => 'package', 'tone' => 'neutral', 'title' => 'Csomagváltási kérelem', 'body' => 'Kérelmét rögzítettük, munkatársunk hamarosan feldolgozza.', 'time' => 'máj. 3.', 'route' => 'plans', 'read' => true],
			['id' => 'n6', 'icon' => 'check-circle', 'tone' => 'success', 'title' => 'Sikeres befizetés', 'body' => 'Az RT-2026-0398 számla befizetését jóváírtuk. Köszönjük!', 'time' => 'máj. 2.', 'route' => 'invoices', 'read' => true],
			['id' => 'n7', 'icon' => 'file-text', 'tone' => 'neutral', 'title' => 'Új dokumentum', 'body' => 'Szerződés-módosítása elérhető a Dokumentumok menüben.', 'time' => 'ápr. 28.', 'route' => 'docs', 'read' => true],
			['id' => 'n8', 'icon' => 'info', 'tone' => 'neutral', 'title' => 'ÁSZF módosítás', 'body' => '2026. június 1-től frissül az Általános Szerződési Feltételek.', 'time' => 'ápr. 24.', 'route' => 'docs', 'read' => true],
			['id' => 'n10', 'icon' => 'user', 'tone' => 'neutral', 'title' => 'Profil adat módosítva', 'body' => 'E-mail címét sikeresen frissítettük.', 'time' => 'ápr. 15.', 'route' => 'profile', 'read' => true],
		];
	}

	/**
	 * Downloadable documents (mirrors portal-docs.jsx PORTAL_DOCS).
	 * cat: mine | main | legal | support; type drives the icon.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function docs(): array
	{
		return [
			['name' => 'Aláírt előfizetői szerződés (2024)', 'file' => 'szerzodes_kis_eva_2024.pdf', 'size' => '410 KB', 'type' => 'contract', 'cat' => 'mine', 'date' => '2024-09-12', 'personal' => true],
			['name' => 'Szerződés-módosítás (csomagváltás)', 'file' => 'szerzodes_modositas_2025-08.pdf', 'size' => '180 KB', 'type' => 'contract', 'cat' => 'mine', 'date' => '2025-08-04', 'personal' => true],
			['name' => 'Előfizetői szerződés', 'file' => 'elofizetoi_szerzodes.docx', 'size' => '142 KB', 'type' => 'contract', 'cat' => 'main', 'date' => null, 'personal' => false],
			['name' => 'Szerződés módosítás', 'file' => 'elofizetes_modositasa.docx', 'size' => '78 KB', 'type' => 'contract', 'cat' => 'main', 'date' => null, 'personal' => false],
			['name' => 'Szerződés felmondás', 'file' => 'elofizetoi_szerzodes_felmondasa.doc', 'size' => '64 KB', 'type' => 'contract', 'cat' => 'main', 'date' => null, 'personal' => false],
			['name' => 'Szolgáltatás szüneteltetése', 'file' => 'szolgaltatas_szuneteltetese.docx', 'size' => '52 KB', 'type' => 'form', 'cat' => 'main', 'date' => null, 'personal' => false],
			['name' => 'Számlázási információk', 'file' => 'szamlazas_informaciok.pdf', 'size' => '120 KB', 'type' => 'form', 'cat' => 'main', 'date' => null, 'personal' => false],
			['name' => 'Általános Szerződési Feltételek (ÁSZF)', 'file' => 'royaltelekom_ASZF_2026.pdf', 'size' => '1.8 MB', 'type' => 'aszf', 'cat' => 'legal', 'date' => null, 'personal' => false],
			['name' => 'ÁSZF kivonat', 'file' => 'aszf_kivonat.pdf', 'size' => '320 KB', 'type' => 'aszf', 'cat' => 'legal', 'date' => null, 'personal' => false],
			['name' => 'Adatvédelmi tájékoztató', 'file' => 'adatvedelem.pdf', 'size' => '210 KB', 'type' => 'legal', 'cat' => 'legal', 'date' => null, 'personal' => false],
			['name' => 'Hibabejelentő nyomtatvány', 'file' => 'hibabejelento.pdf', 'size' => '88 KB', 'type' => 'form', 'cat' => 'support', 'date' => null, 'personal' => false],
			['name' => 'Routerek és eszközök listája', 'file' => 'kompatibilis_eszkozok.pdf', 'size' => '410 KB', 'type' => 'tech', 'cat' => 'support', 'date' => null, 'personal' => false],
		];
	}
}
