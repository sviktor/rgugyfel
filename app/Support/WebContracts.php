<?php

namespace App\Support;

use App\Models\Subscription;
use Illuminate\Support\Carbon;

/**
 * Read layer for the customer-portal Szerződéseim page + the dashboard contracts
 * list: maps a linked customer's active telecom `subscriptions` (with their
 * attached packages) into the contract-card view shape the Blade expects.
 *
 * Telekom subscriptions only (one card per subscription, listing its packages);
 * property leases are not shown on the telecom portal. Always scoped to ONE
 * customer (the authenticated `customers_id`).
 *
 * @example
 *   $cards = WebContracts::forCustomer($user->customers_id);
 */
class WebContracts
{
	/**
	 * The customer's active subscriptions as contract cards.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function forCustomer(int $customerId): array
	{
		if ($customerId <= 0) {
			return [];
		}

		return Subscription::forCustomer($customerId)
			->with(['products', 'property.location'])
			->orderByDesc('id')
			->get()
			->map(static fn (Subscription $s): array => self::card($s))
			->all();
	}

	/**
	 * Map one subscription to the contract-card array.
	 *
	 * @return array<string, mixed>
	 */
	private static function card(Subscription $s): array
	{
		$packages = $s->products->where('deleted', 0);
		$types    = $packages->pluck('type')->filter()->map(static fn ($t): string => (string) $t)->all();
		$hasNet   = self::typesContain($types, 'net');
		$hasTv    = self::typesContain($types, 'tv');

		[$kind, $icon, $kindLabel] = $hasNet
			? ['internet', 'wifi', 'Internet']
			: ($hasTv ? ['tv', 'tv', 'Televízió'] : ['internet', 'wifi', 'Telekom']);

		$name = $packages->pluck('name')->filter()->implode(' + ');
		if ($name === '') {
			$name = (string) ($s->subscription_number ?? 'Telekom előfizetés');
		}

		$start = $s->service_start;

		return [
			'id'        => (string) ($s->contract_number ?: ($s->subscription_number ?: ('#' . $s->id))),
			'kind'      => $kind,
			'kindLabel' => $kindLabel,
			'icon'      => $icon,
			'name'      => $name,
			'address'   => self::address($s),
			'monthly'   => (int) round($s->monthlyFee()),
			'status'    => 'active',
			'summary'   => self::summary($hasNet, $hasTv),
			'metrics'   => [
				['label' => 'Aktiválva', 'value' => $start ? $start->format('Y.m.d.') : '-'],
				['label' => 'Szerződésszám', 'value' => (string) ($s->contract_number ?: ($s->subscription_number ?: '-'))],
				['label' => 'Csomagok', 'value' => $packages->count() . ' db'],
				['label' => 'Hűségidő vége', 'value' => $s->loyaltyExpiry()?->format('Y.m.d.') ?? 'Határozatlan'],
			],
			'loyalty'   => self::loyalty($s),
		];
	}

	/**
	 * Short service chips from the package types present.
	 *
	 * @return array<int, string>
	 */
	private static function summary(bool $hasNet, bool $hasTv): array
	{
		$out = [];
		if ($hasNet) {
			$out[] = 'Internet';
		}
		if ($hasTv) {
			$out[] = 'TV';
		}

		return $out;
	}

	/**
	 * The loyalty progress block, or null when the subscription carries no
	 * loyalty data (the view skips the bar then). `percent` = elapsed / total
	 * clamped 0..100; `alert` set when the expiry is within 60 days.
	 *
	 * @return array{percent: int, end: string, alert: ?string}|null
	 */
	private static function loyalty(Subscription $s): ?array
	{
		$start = $s->service_start;
		$exp   = $s->loyaltyExpiry();
		if ($start === null || $exp === null) {
			return null;
		}

		$total   = (int) $start->diffInDays($exp);
		$elapsed = max(0, (int) $start->diffInDays(Carbon::now(), false));
		$percent = $total > 0 ? (int) round(min(100, max(0, $elapsed / $total * 100))) : 100;

		$alert = null;
		if ($exp->isFuture() && Carbon::now()->diffInDays($exp) <= 60) {
			$alert = 'Hamarosan lejár a hűségidő.';
		}

		return ['percent' => $percent, 'end' => $exp->format('Y-m-d'), 'alert' => $alert];
	}

	/**
	 * The unit address label: the building's postal address (or name) plus the
	 * unit's floor/door breakdown - mirrors PaymentMatrix::rowMeta() unit logic.
	 */
	private static function address(Subscription $s): string
	{
		$property = $s->property;
		if ($property === null) {
			return '';
		}

		$unit = implode(' / ', array_filter(
			[(string) ($property->building ?? ''), (string) ($property->floor ?? ''), (string) ($property->door ?? ''), (string) ($property->apartment_no ?? '')],
			static fn (string $v): bool => trim($v) !== '',
		));
		if ($unit === '' && trim((string) $property->name) !== '') {
			$unit = (string) $property->name;
		}

		$base = trim((string) ($property->location?->address ?: $property->location?->name ?: ''));

		return trim($base . ($unit !== '' ? ' ' . $unit : ''));
	}

	/**
	 * Whether any package type token contains the needle (e.g. 'tv net' has both
	 * 'tv' and 'net').
	 *
	 * @param  array<int, string>  $types
	 */
	private static function typesContain(array $types, string $needle): bool
	{
		foreach ($types as $type) {
			if (str_contains($type, $needle)) {
				return true;
			}
		}

		return false;
	}
}
