<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ContractRequest - a pending "link this contract to my account" request
 * (cus_contract_requests). Created at registration and by the dashboard
 * "Szerződés hozzárendelése" form; approved later by staff in rgadmin.
 *
 * @property int         $id
 * @property int         $cus_users_id
 * @property string|null $contract_number
 * @property \Illuminate\Support\Carbon|null $birth_date
 * @property string      $status            pending | approved | rejected
 * @property int|null    $customers_id
 */
class ContractRequest extends Model
{
	protected $table = 'cus_contract_requests';

	protected $fillable = [
		'cus_users_id', 'contract_number', 'birth_date', 'note',
		'status', 'customers_id', 'reviewed_at',
	];

	protected $casts = [
		'birth_date'  => 'date',
		'reviewed_at' => 'datetime',
	];

	/**
	 * The portal account that filed the request.
	 */
	public function user(): BelongsTo
	{
		return $this->belongsTo(CustomerUser::class, 'cus_users_id');
	}

	/**
	 * Does the request carry both identification fields (contract number + birth
	 * date)? A complete pending request is ready for staff approval; an
	 * incomplete one (e.g. the empty row from a data-less registration) still
	 * waits for the customer to supply the data on the dashboard. Mirrors the
	 * rgadmin CusContractRequest::readyForApproval() condition.
	 *
	 * @example  if (! $request->isComplete()) { /* show "Adatok megadására vár" *\/ }
	 */
	public function isComplete(): bool
	{
		return trim((string) $this->contract_number) !== '' && $this->birth_date !== null;
	}

	/**
	 * Hungarian status label for the portal "Függő kérelmek" list.
	 *
	 * @example  $request->statusLabel() // 'Jóváhagyásra vár'
	 */
	public function statusLabel(): string
	{
		return $this->isComplete() ? 'Jóváhagyásra vár' : 'Adatok megadására vár';
	}
}
