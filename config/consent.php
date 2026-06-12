<?php

/*
 * GDPR cookie-consent settings (banner + settings modal, see
 * partials/cookie-consent.blade.php + App\Support\CookieConsent).
 */
return [

	/*
	 * How long (in days) the visitor's consent decision is remembered -
	 * the max-age of the consent cookie. Test environments use 1 day
	 * (COOKIE_CONSENT_DAYS=1 in .env) so the banner can be re-tested daily;
	 * production stays on the 180-day default.
	 */
	'days' => (int) env('COOKIE_CONSENT_DAYS', 180),

];
