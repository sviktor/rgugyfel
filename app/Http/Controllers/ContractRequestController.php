<?php

namespace App\Http\Controllers;

use App\Models\ContractRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * ContractRequestController - the logged-in dashboard "Szerződés hozzárendelése"
 * form. Files a PENDING cus_contract_requests row (staff approve it in rgadmin),
 * then returns to the dashboard with a confirmation shown in the ptAlert
 * lightbox (the session `pt_alert` flash bridge).
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

		ContractRequest::create([
			'cus_users_id'    => Auth::guard('customer')->id(),
			'contract_number' => $data['contract_number'],
			'birth_date'      => $data['birth_date'],
			'status'          => 'pending',
		]);

		return redirect()->route('dashboard')->with('pt_alert', [
			'variant' => 'success',
			'title'   => 'Kérelmét rögzítettük',
			'message' => 'A szerződés-hozzárendelési kérelmét továbbítottuk munkatársaink részére. '
				. 'A jóváhagyás 1-2 munkanapon belül megtörténik, az eredményről e-mailben értesítjük.',
		]);
	}
}
