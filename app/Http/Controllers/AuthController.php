<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Auth controller - login / register / forgot (visual mockup).
 *
 * No real authentication yet: the `customers` table will land via
 * rgadmin's migration, and we'll wire a custom Eloquent user provider
 * (App\Models\Customer extends Authenticatable, $table = 'customers')
 * once that exists. See rgugyfel/CLAUDE.md → "Auth setup".
 *
 * For now every form fake-succeeds and redirects.
 */
class AuthController extends Controller
{
	public function showLogin(): View    { return view('auth.login'); }
	public function showRegister(): View { return view('auth.register'); }
	public function showForgot(): View   { return view('auth.forgot'); }

	/**
	 * Fake-login - redirects to dashboard regardless of credentials.
	 *
	 * @example  POST /login  →  AuthController::login()
	 */
	public function login(Request $request): RedirectResponse
	{
		// TODO: validate + Auth::guard('customer')->attempt(...) once the
		// `customers` table and Customer model exist.
		return redirect()->route('dashboard');
	}

	/**
	 * Fake-register - redirects to dashboard.
	 *
	 * @example  POST /regisztracio  →  AuthController::register()
	 */
	public function register(Request $request): RedirectResponse
	{
		// TODO: validate + create Customer row + Auth::login(...)
		return redirect()->route('dashboard');
	}

	/**
	 * Fake-forgot - flashes the email and renders the "sent" state.
	 *
	 * @example  POST /elfelejtett-jelszo  →  AuthController::forgot()
	 */
	public function forgot(Request $request): RedirectResponse
	{
		$request->validate(['email' => 'required|email|max:200']);
		// TODO: Password::broker('customers')->sendResetLink(...) later.
		return redirect()->route('forgot')->with([
			'forgot_sent'  => true,
			'forgot_email' => $request->input('email'),
		]);
	}

	/**
	 * Logout - redirects to the login screen.
	 *
	 * @example  POST /logout  →  AuthController::logout()
	 */
	public function logout(Request $request): RedirectResponse
	{
		// TODO: Auth::guard('customer')->logout(); $request->session()->invalidate();
		return redirect()->route('login');
	}
}
