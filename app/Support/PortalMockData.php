<?php

namespace App\Support;

/**
 * Royal Telekom Portal - view formatters.
 *
 * Originally the portal's mock-data source; the mock DATA has been removed now
 * that the pages render real data (invoices via {@see WebInvoices}, contracts via
 * {@see WebContracts}, packages via {@see WebPackages}; unlinked accounts show no
 * customer data at all). Only the small formatting helpers used across the portal
 * Blade views remain here.
 *
 * (The class name is kept to avoid churning every `PortalMockData::huf/date`
 * reference in the views; it could be renamed to PortalFormat in a later pass.)
 */
final class PortalMockData
{
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

	/** Whole days from today until $iso (negative if already past). */
	public static function daysUntil(string $iso): int
	{
		return (int) round((strtotime($iso) - strtotime('today')) / 86400);
	}

	/** Whole days from $iso ago until today (negative if still in the future). */
	public static function daysAgo(string $iso): int
	{
		return (int) round((strtotime('today') - strtotime($iso)) / 86400);
	}
}
