<?php

namespace App\Http\Controllers;

use App\Models\CustomerUser;
use App\Models\LoginAttempt;
use App\Support\CustomerMail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

/**
 * AuthController - portal login + logout against the cus_users accounts.
 *
 * The forms submit via AJAX (resources/js/auth-forms.js): a JSON
 * `{redirect: url}` on success, a `{title, message}` / `{errors}` 422 on
 * failure (shown in the ptAlert lightbox). See rgugyfel/CLAUDE.md -> Auth.
 */
class AuthController extends Controller
{
	/** Failed attempts inside this window trip the lockout. */
	private const ATTEMPT_WINDOW_MINUTES = 60;

	/** Number of failures (within the window) that locks the account. */
	private const MAX_ATTEMPTS = 5;

	/** Remember-me cookie lifetime: 180 days (in minutes). */
	private const REMEMBER_MINUTES = 60 * 24 * 180;

	public function showLogin(): View
	{
		return view('auth.login');
	}

	/**
	 * Authenticate a customer. Resolves the typed identifier (e-mail OR linked
	 * customer number), enforces the lockout + e-mail-verification gates, and
	 * logs in with a 180-day remember cookie when requested.
	 *
	 * @example  POST /login  ->  {redirect: '/'}
	 */
	public function login(Request $request): JsonResponse
	{
		$request->validate([
			'login'    => 'required|string',
			'password' => 'required|string',
		], [
			'login.required'    => 'Kérjük, adja meg e-mail címét vagy ügyfél-azonosítóját.',
			'password.required' => 'A belépéshez kérjük, adja meg jelszavát.',
		]);

		$email = $this->resolveEmail((string) $request->input('login'));
		$user  = CustomerUser::where('email', $email)->first();

		// Lockout gate - applies before any password check.
		if ($user && $user->isLocked()) {
			return response()->json([
				'title'   => 'Fiók átmenetileg zárolva',
				'message' => 'Túl sok sikertelen belépési kísérlet miatt fiókját átmenetileg zároltuk. '
					. 'Biztonsági okból kérjük, próbálja meg újra 2 nap múlva.',
			], 422);
		}

		$credentials = ['email' => $email, 'password' => (string) $request->input('password')];

		// validate() checks credentials WITHOUT logging in.
		if (! $user || ! Auth::guard('customer')->validate($credentials)) {
			$this->recordFailure($email, $request->ip(), $user);

			return response()->json([
				'title'   => 'Sikertelen belépés',
				'message' => 'A megadott e-mail cím / azonosító vagy jelszó helytelen.',
			], 422);
		}

		// Credentials OK but the e-mail is not confirmed yet -> resend + notice.
		if (! $user->hasVerifiedEmail()) {
			$this->sendVerification($user);
			$request->session()->put('verify_email', $user->email);
			$request->session()->flash('pt_alert', [
				'variant' => 'info',
				'title'   => 'Erősítse meg e-mail címét',
				'message' => 'A belépéshez először erősítse meg e-mail címét. Újra elküldtük a megerősítő linket.',
			]);

			return response()->json(['redirect' => route('register.verify.notice')]);
		}

		// Success - clear the failure trail and log in with the remember cookie.
		LoginAttempt::where('email', $email)->delete();
		if ($user->locked_until !== null) {
			$user->forceFill(['locked_until' => null])->save();
		}

		$guard = Auth::guard('customer');
		$guard->setRememberDuration(self::REMEMBER_MINUTES);
		$guard->login($user, $request->boolean('remember'));
		$request->session()->regenerate();

		return response()->json(['redirect' => route('dashboard')]);
	}

	/**
	 * Log the customer out and return to the login screen.
	 *
	 * @example  POST /logout  ->  redirect to /login
	 */
	public function logout(Request $request): RedirectResponse
	{
		Auth::guard('customer')->logout();
		$request->session()->invalidate();
		$request->session()->regenerateToken();

		return redirect()->route('login');
	}

	/**
	 * Resolve the typed login identifier to an e-mail. Plain e-mails pass
	 * through; otherwise we look up a cus_users account whose LINKED customer
	 * carries the typed customer number.
	 */
	private function resolveEmail(string $login): string
	{
		$login = trim($login);
		if ($login === '' || str_contains($login, '@')) {
			return $login;
		}

		$user = CustomerUser::whereHas('customer', function ($q) use ($login) {
			$q->where('customer_number', $login);
		})->first();

		return $user?->email ?? $login;
	}

	/**
	 * Log a failed attempt and lock the account after MAX_ATTEMPTS within the
	 * rolling window (the lockout lasts 1 day - the user is told "2 days" :)).
	 */
	private function recordFailure(string $email, ?string $ip, ?CustomerUser $user): void
	{
		LoginAttempt::create(['email' => $email, 'ip' => $ip, 'created_at' => now()]);

		if (! $user) {
			return;
		}

		$recent = LoginAttempt::where('email', $email)
			->where('created_at', '>=', now()->subMinutes(self::ATTEMPT_WINDOW_MINUTES))
			->count();

		if ($recent >= self::MAX_ATTEMPTS) {
			$user->forceFill(['locked_until' => now()->addDay()])->save();
		}
	}

	/**
	 * (Re)send the e-mail verification link (24h signed URL).
	 */
	private function sendVerification(CustomerUser $user): void
	{
		$url = URL::temporarySignedRoute('verification.verify', now()->addDay(), [
			'id'   => $user->id,
			'hash' => sha1($user->email),
		]);

		CustomerMail::send('customer_verify_email', $user->email, [
			'{neve}'       => $user->name,
			'{verify_url}' => $url,
		]);
	}
}
