<?php

namespace App\Http\Middleware;

use App\Support\SiteContent;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Portal on/off gate, driven by the CMS "Oldal elérhetőség" setting
 * (web_sections: portal / global.availability, edited in rgadmin under
 * WEBOLDALAK -> Ügyfélkapu -> Beállítások).
 *
 * - online = '1' (or the row is missing - fail-open): pass through.
 * - online = '0': visitors get the maintenance view (503). An admin can still
 *   browse via the preview key: opening <portal>/?elonezet=<preview_token> sets
 *   a session flag, after which the WHOLE portal renders normally with a red
 *   "az Ügyfélkapu jelenleg ki van kapcsolva" banner (the layouts read the
 *   shared $siteOffline flag).
 *
 * Mirror of the identical rgtelekom gate; only the CMS site key differs.
 *
 * @example  ->withMiddleware(fn (Middleware $m) => $m->web(append: SiteAvailability::class))
 */
class SiteAvailability
{
	public function __construct(private SiteContent $cms)
	{
	}

	/**
	 * Gate the request by the CMS availability switch.
	 *
	 * @example  GET /?elonezet=<token>  // unlocks preview for the session
	 */
	public function handle(Request $request, Closure $next): Response
	{
		if ($this->cms->get('global.availability.online') !== '0') {
			return $next($request);
		}

		// Portal is OFF. The preview key unlocks browsing for this session.
		$token = $this->cms->get('global.availability.preview_token');
		if ($token !== '' && $request->query('elonezet') === $token) {
			$request->session()->put('site_preview', true);
		}

		if ($request->session()->get('site_preview') === true) {
			View::share('siteOffline', true); // the layout shows the red banner
			return $next($request);
		}

		return response()->view('maintenance', [], 503);
	}
}
