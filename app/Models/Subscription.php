<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * Subscription - READ-ONLY view of the shared `subscriptions` table (owned by
 * rgadmin, the telecom service contracts). The portal reads a linked customer's
 * subscriptions + their attached packages to render the Szerződéseim cards; it
 * never writes this table.
 *
 * The fee + loyalty rules mirror rgadmin's model verbatim (Subscription::
 * monthlyFee() / loyaltyExpiry()) so the portal shows the same numbers as the
 * admin.
 *
 * @property int         $id
 * @property int|null    $customers_id
 * @property int|null    $properties_id
 * @property string|null $subscription_number
 * @property string|null $contract_number
 * @property string|null $monthly_fee
 * @property int|null    $loyalty_months
 * @property int         $status
 * @property int         $deleted
 */
class Subscription extends Model
{
	protected $table   = 'subscriptions';
	public $timestamps = false;

	protected $casts = [
		'customers_id'   => 'integer',
		'properties_id'  => 'integer',
		'monthly_fee'    => 'decimal:2',
		'contract_date'  => 'date',
		'service_start'  => 'date',
		'loyalty_months' => 'integer',
		'status'         => 'integer',
		'deleted'        => 'integer',
	];

	/**
	 * The property unit receiving the service.
	 */
	public function property(): BelongsTo
	{
		return $this->belongsTo(Property::class, 'properties_id');
	}

	/**
	 * The subscribing customer.
	 */
	public function customer(): BelongsTo
	{
		return $this->belongsTo(Customer::class, 'customers_id');
	}

	/**
	 * The packages attached to this subscription (the `subscriptions_products` M:N).
	 *
	 * @example  $subscription->products->pluck('name')->implode(', ')
	 */
	public function products(): BelongsToMany
	{
		return $this->belongsToMany(Product::class, 'subscriptions_products', 'subscriptions_id', 'products_id');
	}

	/**
	 * Effective monthly fee: the monthly_fee override when set (> 0), otherwise
	 * the sum of the linked products' brutto prices. Mirror of rgadmin's rule;
	 * uses the loaded `products` relation (eager-load it on lists).
	 *
	 * @example  $subscription->monthlyFee()   // 12990.0
	 */
	public function monthlyFee(): float
	{
		if ($this->monthly_fee !== null && (float) $this->monthly_fee > 0) {
			return (float) $this->monthly_fee;
		}
		return (float) $this->products->where('deleted', 0)->sum('brutto');
	}

	/**
	 * Loyalty expiry: service_start + loyalty_months, or null when either part is
	 * missing. Mirror of rgadmin's rule; computed, never stored.
	 *
	 * @example  $subscription->loyaltyExpiry()?->format('Y-m-d')   // '2027-04-30' | null
	 */
	public function loyaltyExpiry(): ?Carbon
	{
		if (! $this->service_start || ! $this->loyalty_months) {
			return null;
		}
		return $this->service_start->copy()->addMonths((int) $this->loyalty_months);
	}

	/**
	 * Scope: a customer's own active, non-deleted subscriptions.
	 *
	 * @example  Subscription::forCustomer(42)->with('products', 'property.location')->get()
	 */
	public function scopeForCustomer(Builder $q, int $customerId): Builder
	{
		return $q->where('customers_id', $customerId)
			->where('status', 1)
			->where('deleted', 0);
	}
}
