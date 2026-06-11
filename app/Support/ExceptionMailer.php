<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Sends an error-notification e-mail for an uncaught exception (a real "500").
 * Wired from bootstrap/app.php ->withExceptions() via $exceptions->report().
 * Laravel 11 already skips 404/403/validation/auth (Handler::$internalDontReport),
 * so this only ever sees genuine server errors. The whole body is wrapped in
 * try/catch so a notification failure (mail down, DB-cache down) never breaks
 * the app's own error handling.
 */
class ExceptionMailer
{

	/**
	 * Notify the configured recipients about an uncaught exception. No-op when
	 * disabled, when no recipient is configured, or while throttled. Throttled
	 * per exception signature (class+file+line+message) to avoid e-mail storms.
	 *
	 * @example  \App\Support\ExceptionMailer::notify($e, request());
	 */
	public static function notify(Throwable $e, ?Request $request = null): void
	{
		try {
			if (! config('errors.enabled', true)) {
				return;
			}

			$recipients = array_filter(array_map(
				'trim',
				explode(',', (string) config('errors.mail_to')),
			));
			if ($recipients === []) {
				return;
			}

			// Throttle: one mail per identical signature per window. Cache::add is
			// inside the try, so a DB-backed-cache outage cannot break reporting.
			$key = 'errmail:' . md5(
				$e::class . '|' . $e->getFile() . '|' . $e->getLine() . '|' . $e->getMessage()
			);
			if (! Cache::add($key, 1, (int) config('errors.throttle', 300))) {
				return; // already notified for this signature inside the window
			}

			$body    = view('emails.exception', ['e' => $e, 'request' => $request])->render();
			$subject = 'Hiba a rendszerben: 500 - ' . config('app.name');

			Mail::html($body, static function ($m) use ($recipients, $subject): void {
				$m->to($recipients)->subject($subject);
			});
		} catch (Throwable $inner) {
			Log::error('ExceptionMailer failed', ['error' => $inner->getMessage()]);
		}
	}
}
