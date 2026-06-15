<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * EnsureCustomerEmailVerified - belt-and-suspenders gate for the portal:
 * login already blocks unverified accounts, but if an unverified customer ever
 * reaches an authenticated route, log them out and send them to the verify
 * notice. Registered as the `customer.verified` middleware alias.
 */
class EnsureCustomerEmailVerified
{
	public function handle(Request $request, Closure $next): Response
	{
		$user = Auth::guard('customer')->user();

		if ($user && ! $user->hasVerifiedEmail()) {
			Auth::guard('customer')->logout();
			$request->session()->put('verify_email', $user->email);

			return redirect()->route('register.verify.notice');
		}

		return $next($request);
	}
}
