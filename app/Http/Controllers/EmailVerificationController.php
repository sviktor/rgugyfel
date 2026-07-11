<?php

namespace App\Http\Controllers;

use App\Models\CustomerUser;
use App\Support\CustomerMail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

/**
 * EmailVerificationController - the registration e-mail confirmation flow.
 *
 *  - notice():  the "we sent you a verification e-mail" landing page (shown
 *               right after registration, and when an unverified user tries to
 *               log in).
 *  - verify():  the signed-link handler -> stamps email_verified_at -> renders
 *               the result page (success has a "Belépés" button).
 *  - resend():  re-issues the verification link (throttled route).
 */
class EmailVerificationController extends Controller
{
	public function notice(Request $request): View
	{
		return view('auth.verify-notice', [
			'email' => (string) $request->session()->get('verify_email', ''),
		]);
	}

	/**
	 * Handle the signed verification link. We check the signature MANUALLY (no
	 * `signed` middleware) so an expired/tampered link shows a friendly result
	 * page instead of a bare 403.
	 *
	 * @example  GET /email-megerosites/12/{sha1(email)}?...signature...
	 */
	public function verify(Request $request, int $id, string $hash): View
	{
		if (! $request->hasValidSignature()) {
			return view('auth.verify-result', ['status' => 'expired']);
		}

		$user = CustomerUser::find($id);
		if (! $user || ! hash_equals(sha1($user->email), $hash)) {
			return view('auth.verify-result', ['status' => 'invalid']);
		}

		$user->markEmailVerified();

		return view('auth.verify-result', ['status' => 'success']);
	}

	/**
	 * Re-send the verification link. Always reports success (no e-mail
	 * enumeration); only actually sends if a matching unverified account exists.
	 *
	 * @example  POST /email-megerosites/ujrakuldes
	 */
	public function resend(Request $request): RedirectResponse|JsonResponse
	{
		$request->validate(['email' => 'required|email|max:200'], [
			'email.required' => 'Add meg az e-mail címed.',
			'email.email'    => 'Hibás formátumú e-mail cím.',
			'email.max'      => 'A megadott e-mail cím túl hosszú.',
		]);
		$email = (string) $request->input('email');

		$user = CustomerUser::where('email', $email)->first();
		if ($user && ! $user->hasVerifiedEmail()) {
			$url = URL::temporarySignedRoute('verification.verify', now()->addDay(), [
				'id'   => $user->id,
				'hash' => sha1($user->email),
			]);
			CustomerMail::send('customer_verify_email', $user->email, [
				'{neve}'       => $user->name,
				'{verify_url}' => $url,
			]);
		}

		$request->session()->put('verify_email', $email);
		$request->session()->flash('pt_alert', [
			'variant' => 'success',
			'title'   => 'E-mail újraküldve',
			'message' => 'Ha a megadott címmel van megerősítésre váró fiók, újra elküldtük a megerősítő linket.',
		]);

		// The resend form posts via auth-forms.js (data-auth-form): hand back
		// the redirect target as JSON; a plain (no-JS) post keeps the classic
		// redirect. The pt_alert flash fires on the page loaded next.
		if ($request->expectsJson()) {
			return response()->json(['redirect' => route('register.verify.notice')]);
		}

		return redirect()->route('register.verify.notice');
	}
}
