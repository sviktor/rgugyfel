<?php

namespace App\Http\Controllers;

use App\Models\CustomerUser;
use App\Models\ImpersonationToken;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * ImpersonationController - operator "Belépés az ügyfélkapuba".
 *
 * An rgadmin operator mints a single-use token (shared `mc_rg` DB) and is
 * redirected here to log in as a given cus_users account, so staff can see
 * exactly what the customer sees. The two apps use different APP_KEYs, so trust
 * runs through the one-time DB token, not a Laravel signed URL. The session
 * carries an `impersonation` flag - the topbar shows an operator banner + exit.
 */
class ImpersonationController extends Controller
{
	/**
	 * Consume an impersonation token and log the operator in as the linked
	 * portal account. The token is single-use (claimed atomically) and short
	 * lived; an invalid/expired/used token bounces to the login screen.
	 *
	 * @example  GET /operator-belepes/{token}  ->  redirect to /
	 */
	public function accept(Request $request, string $token): RedirectResponse
	{
		$hash = hash('sha256', $token);
		$row  = ImpersonationToken::where('token', $hash)->first();

		if (! $row || $row->used_at !== null || $row->expires_at->isPast()) {
			return $this->reject();
		}

		// Claim the token atomically - the conditional update on used_at makes a
		// concurrent second hit lose the race (true single-use).
		$claimed = ImpersonationToken::where('id', $row->id)
			->whereNull('used_at')
			->update(['used_at' => now()]);
		if ($claimed !== 1) {
			return $this->reject();
		}

		$user = CustomerUser::find($row->cus_users_id);
		if (! $user) {
			return $this->reject();
		}

		Auth::guard('customer')->login($user);

		// Mark the operator session + drop any prior active-customer choice (mirrors
		// AuthController::login), then rotate the session id.
		$request->session()->put('impersonation', true);
		$request->session()->put('impersonator_admin_id', $row->admin_users_id);
		$request->session()->forget('active_customer_id');
		$request->session()->regenerate();

		return redirect()->route('dashboard');
	}

	/**
	 * Leave operator mode: log the impersonated account out and return to login.
	 *
	 * @example  POST /operator-kilepes  ->  redirect to /login
	 */
	public function exit(Request $request): RedirectResponse
	{
		Auth::guard('customer')->logout();
		$request->session()->invalidate();
		$request->session()->regenerateToken();

		return redirect()->route('login')->with('pt_alert', [
			'variant' => 'info',
			'title'   => 'Kiléptél az operátor-módból',
			'message' => 'Az ügyfél ügyfélkapujából kiléptél.',
		]);
	}

	/**
	 * Bounce an invalid/expired/used impersonation link to the login screen.
	 */
	private function reject(): RedirectResponse
	{
		return redirect()->route('login')->with('pt_alert', [
			'variant' => 'error',
			'title'   => 'Érvénytelen belépő link',
			'message' => 'A belépő link lejárt vagy már felhasználták. Kérd újra az adminban.',
		]);
	}
}
