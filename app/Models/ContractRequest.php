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
		'cus_users_id', 'contract_number', 'birth_date',
		'zip', 'city', 'street', 'note',
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
}
