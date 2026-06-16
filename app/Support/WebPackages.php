<?php

namespace App\Support;

use App\Models\ProductListing;

/**
 * Read layer for the customer-portal Szerződéseim page "Elérhető csomagjaink"
 * showcase. Reads the shared `product_listings` table (owned by rgadmin, edited
 * under Telekom -> Web csomagok - the SAME source as the rgtelekom public
 * /csomagok page) and maps each published card to the compact mini-card shape
 * the portal plans view expects.
 *
 * Read-only; no PlanData fallback (an empty kind simply renders nothing - the
 * portal is informational, there is no plan switching). One query per request
 * (all published rows loaded once, grouped by kind).
 *
 * @example
 *   $groups = WebPackages::availablePlans($currentProductIds);
 */
final class WebPackages
{
	/** kind => list of published ProductListing rows; null until first use. */
	private static ?array $byKind = null;

	/**
	 * The available-plans groups for the portal showcase, keyed by kind
	 * (akcio | internet | telefon). Empty kinds are omitted. A card is flagged
	 * `current` when its `products_id` is one of the customer's package ids.
	 *
	 * @param  array<int, int>  $currentProductIds  the logged-in customer's package ids
	 * @return array<string, array<int, array<string, mixed>>>
	 */
	public static function availablePlans(array $currentProductIds = []): array
	{
		$out = [];
		foreach (['akcio', 'internet', 'telefon'] as $kind) {
			$cards = array_map(
				static fn (ProductListing $r): array => self::mini($r, $kind, $currentProductIds),
				self::rows($kind),
			);
			if ($cards !== []) {
				$out[$kind] = $cards;
			}
		}

		return $out;
	}

	/** Published rows of one kind (loads + groups all kinds once per request). */
	private static function rows(string $kind): array
	{
		if (self::$byKind === null) {
			self::$byKind = ['akcio' => [], 'internet' => [], 'telefon' => []];
			foreach (ProductListing::published()->orderBy('pos')->orderBy('id')->get() as $row) {
				self::$byKind[$row->kind][] = $row;
			}
		}

		return self::$byKind[$kind] ?? [];
	}

	/**
	 * Map one card to the mini-card array the portal `.p-plan-mini` markup uses.
	 *
	 * @param  array<int, int>  $currentProductIds
	 * @return array<string, mixed>
	 */
	private static function mini(ProductListing $r, string $kind, array $currentProductIds): array
	{
		$d = (array) $r->data;
		[$heroNum, $heroUnit] = self::hero($d, $kind);

		return [
			'id'       => (int) $r->id,
			'kind'     => $kind,
			'name'     => (string) $r->name,
			'heroNum'  => $heroNum,
			'heroUnit' => $heroUnit,
			'monthly'  => self::monthly($d, $kind),
			'features' => self::features($d),
			'featured' => (bool) $r->featured,
			'current'  => $r->products_id !== null && in_array((int) $r->products_id, $currentProductIds, true),
		];
	}

	/**
	 * The hero number + unit per kind: internet = speed/Mbps; telefon =
	 * minutes/perc (or the free-text minutes, e.g. "korlátlan"); akció = the
	 * speed when present, else the discount text.
	 *
	 * @return array{0: string, 1: string}
	 */
	private static function hero(array $d, string $kind): array
	{
		if ($kind === 'internet') {
			return [(string) ($d['speed'] ?? ''), 'Mbps'];
		}

		if ($kind === 'telefon') {
			$minutes = trim((string) ($d['minutes'] ?? ''));
			return ctype_digit($minutes) ? [$minutes, 'perc'] : [$minutes, ''];
		}

		// akció
		if (trim((string) ($d['speed'] ?? '')) !== '') {
			return [(string) $d['speed'], 'Mbps'];
		}

		return [trim((string) ($d['discount'] ?? '')), ''];
	}

	/**
	 * The card's monthly price as an int HUF: akció = `price_now`; internet /
	 * telefon = the first active `prices[]` row's value (fallback p1).
	 */
	private static function monthly(array $d, string $kind): int
	{
		if ($kind === 'akcio') {
			return self::toInt($d['price_now'] ?? '');
		}

		if (! empty($d['prices']) && is_array($d['prices'])) {
			foreach ($d['prices'] as $row) {
				if (! is_array($row) || ($row['_active'] ?? '1') === '0') {
					continue;
				}
				$value = self::toInt($row['value'] ?? '');
				if ($value > 0) {
					return $value;
				}
			}
		}

		return self::toInt($d['p1'] ?? '');
	}

	/**
	 * Active feature lines (the `items` repeater values) as a plain string list.
	 *
	 * @return array<int, string>
	 */
	private static function features(array $data): array
	{
		$items = $data['items'] ?? [];
		if (! is_array($items)) {
			return [];
		}

		$out = [];
		foreach ($items as $item) {
			if (! is_array($item) || ($item['_active'] ?? '1') === '0') {
				continue;
			}
			$value = trim((string) ($item['value'] ?? ''));
			if ($value !== '') {
				$out[] = $value;
			}
		}

		return $out;
	}

	/** Parse a stored price scalar ("5 122", "5122") to an int; 0 when non-numeric. */
	private static function toInt(mixed $value): int
	{
		$digits = preg_replace('/[\s\x{00A0}]+/u', '', trim((string) $value));

		return ctype_digit((string) $digits) ? (int) $digits : 0;
	}
}
