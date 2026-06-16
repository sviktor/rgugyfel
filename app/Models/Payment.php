<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Payment - READ-ONLY view of the shared `payments` settlement ledger (owned by
 * rgadmin). The portal reads it to decide whether a customer's invoice is paid:
 * a month is paid iff a `settlement` row exists for (payable, month); a `credit`
 * row reduces the still-owed amount (partial payment). The portal never writes
 * this table - settlements/credits are recorded by staff in the rgadmin matrix.
 *
 * Queried by the (payable_type, payable_id, period, kind) columns only - no
 * morph relation is needed here.
 *
 * @property int         $id
 * @property string|null $payable_type  'subscription' | 'lease'
 * @property int|null    $payable_id
 * @property string      $kind          'settlement' | 'extra' | 'credit'
 * @property \Illuminate\Support\Carbon $period
 * @property int         $amount
 */
class Payment extends Model
{
	public const KIND_SETTLEMENT = 'settlement';
	public const KIND_EXTRA      = 'extra';
	public const KIND_CREDIT     = 'credit';

	protected $table   = 'payments';
	public $timestamps = false;

	protected $casts = [
		'payable_id' => 'integer',
		'period'     => 'date',
		'amount'     => 'integer',
	];
}
