<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * Recaptcha - server-side Google reCAPTCHA v2 verification (port of rgsite's
 * ContactController::verifyRecaptcha). Gates the portal registration form.
 */
class Recaptcha
{
	/**
	 * Is reCAPTCHA active (enabled + a site key configured)?
	 *
	 * @example  if (Recaptcha::enabled()) { /* render the widget *\/ }
	 */
	public static function enabled(): bool
	{
		return (bool) config('recaptcha.enabled') && config('recaptcha.site_key') !== '';
	}

	/**
	 * Verify the submitted reCAPTCHA token against Google. Returns true when
	 * reCAPTCHA is disabled (nothing to check).
	 *
	 * @example  if (! Recaptcha::verify($request)) { /* reject *\/ }
	 */
	public static function verify(Request $request): bool
	{
		if (! self::enabled()) {
			return true;
		}

		$token = (string) $request->input('g-recaptcha-response', '');
		if ($token === '') {
			return false;
		}

		$response = Http::asForm()
			->timeout(5)
			->post(config('recaptcha.verify_url'), [
				'secret'   => config('recaptcha.secret_key'),
				'response' => $token,
				'remoteip' => $request->ip(),
			]);

		return (bool) ($response->json('success') ?? false);
	}
}
