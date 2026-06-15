<?php

namespace App\Support;

use App\Models\EmailTemplate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * CustomerMail - sends a portal e-mail from a shared `email_templates` row
 * (verify / password reset), with built-in fallback content. Self-guarded: a
 * mail-transport failure is logged, never thrown (mirrors the ExceptionMailer
 * pattern), so an SMTP hiccup never breaks the registration / reset flow.
 */
class CustomerMail
{
	/**
	 * Render the named template and send it as an HTML mail.
	 *
	 * @example
	 *   CustomerMail::send('customer_verify_email', $user->email, [
	 *       '{neve}' => $user->name, '{verify_url}' => $url,
	 *   ]);
	 *
	 * @param  array<string, string>  $vars  {variable} => replacement value
	 */
	public static function send(string $alias, string $to, array $vars = []): void
	{
		// Always provide the portal name unless the caller overrode it.
		$vars = array_merge(['{cim}' => config('app.name', 'Royal Telekom Ügyfélkapu')], $vars);

		$mail = EmailTemplate::useTemplate($alias, $vars);
		if ($mail['body'] === '') {
			return;
		}

		try {
			Mail::html($mail['body'], function ($m) use ($to, $mail) {
				$m->to($to)->subject($mail['subject']);
			});
		} catch (\Throwable $e) {
			Log::warning('Portal mail failed', ['alias' => $alias, 'to' => $to, 'error' => $e->getMessage()]);
		}
	}
}
