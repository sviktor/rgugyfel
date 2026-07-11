<?php

namespace App\Support;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

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
	 * reCAPTCHA is disabled (nothing to check). When Google itself is
	 * unreachable the check FAILS CLOSED: a ValidationException with an honest
	 * "try again later" message is thrown instead of a 500 - registration must
	 * not proceed unverified.
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

		try {
			$response = Http::asForm()
				->timeout(5)
				->post(config('recaptcha.verify_url'), [
					'secret'   => config('recaptcha.secret_key'),
					'response' => $token,
					'remoteip' => $request->ip(),
				]);
		} catch (ConnectionException $e) {
			Log::warning('reCAPTCHA verify unreachable, rejecting submission: ' . $e->getMessage());

			throw ValidationException::withMessages([
				'g-recaptcha-response' => 'A robot-ellenőrzés átmenetileg nem elérhető. Próbáld újra néhány perc múlva.',
			]);
		}

		return (bool) ($response->json('success') ?? false);
	}
}
