<?php

namespace App\Http\Controllers;

use App\Models\ContractRequest;
use App\Support\SiteContent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * ContractRequestController - the logged-in "Szerződés hozzárendelése" form
 * (shared by the dashboard and the Szerződéseim page). Files a PENDING
 * cus_contract_requests row (staff approve it in rgadmin).
 *
 * The form posts via AJAX (the portal's `data-auth-form` mechanism in
 * resources/js/auth-forms.js): a 422 surfaces the validation errors in the
 * ptAlert lightbox in place, while a success hands back a `redirect` target so
 * the browser reloads the originating page - the `pt_alert` flash then fires the
 * confirmation modal AND the card re-renders with the freshly filed pending
 * request.
 *
 * If the account still has an INCOMPLETE pending request (e.g. the empty row
 * created when the customer registered without identification data), this form
 * COMPLETES that row instead of creating a duplicate; otherwise it adds a new
 * request (a portal account may hold unlimited contracts).
 */
class ContractRequestController extends Controller
{
	/**
	 * @example  POST /szerzodes-igenyles  ->  {redirect: '/'} (+ pt_alert flash)
	 */
	public function store(Request $request): JsonResponse
	{
		$data = $request->validate([
			'contract_number' => 'required|string|max:50',
			'birth_date'      => 'required|date|before:today',
		], [
			'contract_number.required' => 'Kérjük, adja meg a szerződésszámot.',
			'birth_date.required'      => 'Kérjük, adja meg a szerződő születési dátumát.',
			'birth_date.before'        => 'Kérjük, érvényes születési dátumot adjon meg.',
		]);

		$userId = (int) Auth::guard('customer')->id();

		// Complete an existing incomplete pending request (the empty row from a
		// data-less registration), else file a new one.
		$incomplete = ContractRequest::where('cus_users_id', $userId)
			->where('status', 'pending')
			->where(static function (Builder $w): void {
				$w->whereNull('contract_number')->orWhere('contract_number', '=', '')->orWhereNull('birth_date');
			})
			->orderBy('id')
			->first();

		if ($incomplete) {
			$incomplete->update([
				'contract_number' => $data['contract_number'],
				'birth_date'      => $data['birth_date'],
			]);
		} else {
			ContractRequest::create([
				'cus_users_id'    => $userId,
				'contract_number' => $data['contract_number'],
				'birth_date'      => $data['birth_date'],
				'status'          => 'pending',
			]);
		}

		// Confirmation copy is operator-editable in rgadmin (WEBOLDALAK ->
		// Ügyfélkapu -> Főoldal -> Szerződés hozzárendelése); fall back to the
		// built-in text before the editor is first opened. Flashed for the
		// reloaded page's ptAlert bridge.
		$cms = app(SiteContent::class);

		$request->session()->flash('pt_alert', [
			'variant' => 'success',
			'title'   => $cms->get('home.add_contract.confirm_title') ?: 'Kérelmét rögzítettük',
			'message' => $cms->get('home.add_contract.confirm_message')
				?: 'A szerződés-hozzárendelési kérelmét továbbítottuk munkatársaink részére. '
					. 'A jóváhagyás 1-2 munkanapon belül megtörténik, az eredményről e-mailben értesítjük.',
		]);

		return response()->json(['redirect' => $this->returnTo($request)]);
	}

	/**
	 * Resolve the page to reload after a successful submit. The form passes its
	 * own URL in `return_to`; we only honour it when it is one of the two pages
	 * the card lives on (dashboard / Szerződéseim), so the value can never become
	 * an open redirect. Anything else falls back to the dashboard.
	 *
	 * @example  $this->returnTo($request) // route('plans') when posted from /szerzodeseim
	 */
	private function returnTo(Request $request): string
	{
		$target = (string) $request->input('return_to', '');

		foreach (['dashboard', 'plans'] as $name) {
			$url = route($name);
			if ($target === $url || $target === rtrim($url, '/')) {
				return $url;
			}
		}

		return route('dashboard');
	}
}
