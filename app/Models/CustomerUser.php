<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * CustomerUser - a Royal Telekom customer-portal login account (cus_users).
 *
 * The rgugyfel-owned authentication identity, separate from the shared CRM
 * `customers` table. A row is created by self-registration and is LINKED to a
 * `customers` record only once a staff member approves the contract request
 * (cus_contract_requests) - until then `customers_id` is NULL.
 *
 * @property int         $id
 * @property int|null    $customers_id
 * @property string      $name
 * @property string      $email
 * @property string|null $phone
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property \Illuminate\Support\Carbon|null $locked_until
 * @property int         $status
 */
class CustomerUser extends Authenticatable
{
	use HasFactory;

	protected $table = 'cus_users';

	protected $fillable = [
		'customers_id', 'name', 'email', 'phone', 'birth_date', 'password',
		'email_verified_at', 'locked_until', 'status', 'settings',
	];

	protected $hidden = [
		'password', 'remember_token',
	];

	protected function casts(): array
	{
		return [
			'email_verified_at' => 'datetime',
			'locked_until'      => 'datetime',
			'birth_date'        => 'date',
			'password'          => 'hashed',
			'status'            => 'integer',
			'settings'          => 'array',
		];
	}

	/**
	 * The linked CRM customer (NULL until a contract request is approved).
	 */
	public function customer(): BelongsTo
	{
		return $this->belongsTo(Customer::class, 'customers_id');
	}

	/**
	 * The account's contract-link requests (registration + dashboard).
	 */
	public function contractRequests(): HasMany
	{
		return $this->hasMany(ContractRequest::class, 'cus_users_id');
	}

	/**
	 * Is the account currently locked out by the brute-force guard?
	 *
	 * @example  if ($user->isLocked()) { /* show lockout modal *\/ }
	 */
	public function isLocked(): bool
	{
		return $this->locked_until !== null && $this->locked_until->isFuture();
	}

	/**
	 * Has the account confirmed its e-mail address?
	 *
	 * @example  if (! $user->hasVerifiedEmail()) { /* block login *\/ }
	 */
	public function hasVerifiedEmail(): bool
	{
		return $this->email_verified_at !== null;
	}

	/**
	 * Stamp the e-mail as verified (idempotent).
	 *
	 * @example  $user->markEmailVerified();
	 */
	public function markEmailVerified(): void
	{
		if (! $this->hasVerifiedEmail()) {
			$this->forceFill(['email_verified_at' => now()])->save();
		}
	}
}
