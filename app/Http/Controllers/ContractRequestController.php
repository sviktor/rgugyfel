<?php

namespace App\Http\Controllers;

use App\Models\ContractRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * ContractRequestController - the logged-in dashboard "Szerződés hozzárendelése"
 * form. Files a PENDING cus_contract_requests row (staff approve it in rgadmin),
 * then returns to the dashboard with a confirmation shown in the ptAlert
 * lightbox (the session `pt_alert` flash bridge).
 *
 * If the account still has an INCOMPLETE pending request (e.g. the empty row
 * created when the customer registered without identification data), this form
 * COMPLETES that row instead of creating a duplicate; otherwise it adds a new
 * request (a portal account may hold unlimited contracts).
 */
class ContractRequestController extends Controller
{
	/**
	 * @example  POST /szerzodes-igenyles  ->  redirect back to the dashboard
	 */
	public function store(Request $request): RedirectResponse
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

		return redirect()->route('dashboard')->with('pt_alert', [
			'variant' => 'success',
			'title'   => 'Kérelmét rögzítettük',
			'message' => 'A szerződés-hozzárendelési kérelmét továbbítottuk munkatársaink részére. '
				. 'A jóváhagyás 1-2 munkanapon belül megtörténik, az eredményről e-mailben értesítjük.',
		]);
	}
}
