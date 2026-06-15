<?php

namespace App\Http\Controllers;

use App\Models\CustomerUser;
use App\Support\CustomerMail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * PasswordResetController - "elfelejtett jelszó" + the set-new-password flow.
 *
 * Uses Laravel's `customers` password broker (cus_password_reset_tokens, 30-min
 * tokens) for secure token storage/validation, but sends our OWN branded mail
 * via the shared email_templates (customer_password_reset). The request form
 * never reveals whether an address exists (anti-enumeration).
 */
class PasswordResetController extends Controller
{
	public function requestForm(): View
	{
		return view('auth.forgot');
	}

	/**
	 * Issue a reset token + mail (always reports "sent").
	 *
	 * @example  POST /elfelejtett-jelszo  ->  {redirect: '/elfelejtett-jelszo'}
	 */
	public function sendLink(Request $request): JsonResponse
	{
		$request->validate(
			['email' => 'required|email|max:200'],
			['email.required' => 'Kérjük, adja meg e-mail címét.', 'email.email' => 'Kérjük, adjon meg egy érvényes e-mail címet.']
		);

		$email = (string) $request->input('email');
		$user  = CustomerUser::where('email', $email)->first();

		if ($user) {
			$token = Password::broker('customers')->createToken($user);
			$url   = route('password.reset', ['token' => $token]) . '?email=' . urlencode($email);

			CustomerMail::send('customer_password_reset', $email, [
				'{neve}'        => $user->name,
				'{reset_url}'   => $url,
				'{ervenyesseg}' => '30',
			]);
		}

		// Same response whether or not the address exists.
		$request->session()->flash('forgot_sent', true);
		$request->session()->flash('forgot_email', $email);

		return response()->json(['redirect' => route('forgot')]);
	}

	/**
	 * The set-new-password screen reached from the e-mail link.
	 *
	 * @example  GET /jelszo-visszaallitas/{token}?email=...
	 */
	public function resetForm(Request $request, string $token): View
	{
		return view('auth.reset', [
			'token' => $token,
			'email' => (string) $request->query('email', ''),
		]);
	}

	/**
	 * Apply the new password via the broker, then send the user to login.
	 *
	 * @example  POST /jelszo-visszaallitas  ->  {redirect: '/login'}
	 */
	public function reset(Request $request): JsonResponse
	{
		$request->validate([
			'token'    => 'required',
			'email'    => 'required|email',
			'password' => ['required', 'string', 'min:10', 'confirmed', 'regex:/[A-Z]/', 'regex:/[0-9]/'],
		], [
			'password.min'       => 'A jelszónak legalább 10 karakter hosszúnak kell lennie.',
			'password.confirmed' => 'A két megadott jelszó nem egyezik.',
			'password.regex'     => 'A jelszónak tartalmaznia kell nagybetűt és számot is.',
		]);

		$status = Password::broker('customers')->reset(
			$request->only('email', 'password', 'password_confirmation', 'token'),
			function (CustomerUser $user, string $password) {
				// The 'hashed' cast hashes on set - store the PLAIN value.
				$user->forceFill(['password' => $password, 'locked_until' => null])->save();
			}
		);

		if ($status !== Password::PASSWORD_RESET) {
			throw ValidationException::withMessages([
				'email' => 'A jelszó-helyreállító link érvénytelen vagy lejárt. Kérjen újat.',
			]);
		}

		$request->session()->flash('pt_alert', [
			'variant' => 'success',
			'title'   => 'Jelszó módosítva',
			'message' => 'Új jelszavát beállítottuk. Most már bejelentkezhet.',
		]);

		return response()->json(['redirect' => route('login')]);
	}
}
