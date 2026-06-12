<?php

namespace App\Support;

/**
 * Server-side reader of the visitor's GDPR cookie-consent decision.
 *
 * The decision is written by resources/js/cookie-consent.js into a plain
 * (unencrypted - excepted from EncryptCookies in bootstrap/app.php) cookie
 * as JSON: {"choices":{"necessary":true,"preferences":bool,"statistics":bool,
 * "marketing":bool},"timestamp":"...","version":1}. Lifetime comes from
 * COOKIE_CONSENT_DAYS (config/consent.php). The banner/modal markup lives in
 * resources/views/partials/cookie-consent.blade.php.
 */
class CookieConsent
{
	/** Consent cookie name - keep in sync with the cookie-consent partial. */
	public const COOKIE = 'cp-cookie-consent-v1';

	/**
	 * The saved consent record, or null when the visitor has not decided yet.
	 *
	 * @return array|null decoded record: ['choices' => array, 'timestamp' => string, 'version' => int]
	 * @example CookieConsent::record()['timestamp'] ?? null
	 */
	public static function record(): ?array
	{
		$raw = request()->cookie(self::COOKIE);
		if (! is_string($raw) || $raw === '') {
			return null;
		}
		$decoded = json_decode($raw, true);

		return (is_array($decoded) && is_array($decoded['choices'] ?? null)) ? $decoded : null;
	}

	/**
	 * Whether the visitor consented to a cookie category.
	 *
	 * 'necessary' is always allowed (GDPR 6(1)(f)); every other category
	 * needs an explicit opt-in stored in the consent cookie.
	 *
	 * @param string $category necessary|preferences|statistics|marketing
	 * @example @if (\App\Support\CookieConsent::allows('statistics')) ...GA snippet... @endif
	 */
	public static function allows(string $category): bool
	{
		if ($category === 'necessary') {
			return true;
		}

		return (bool) (self::record()['choices'][$category] ?? false);
	}
}
