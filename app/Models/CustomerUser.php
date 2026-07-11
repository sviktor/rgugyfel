<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\DB;

/**
 * CustomerUser - a Royal Telekom customer-portal login account (cus_users).
 *
 * The rgugyfel-owned authentication identity, separate from the shared CRM
 * `customers` table. A row is created by self-registration and is LINKED to
 * `customers` records through the `cus_users_customers` pivot, one link added
 * per staff-approved contract request (cus_contract_requests) - one account may
 * hold N customers (a relative's or a second property's service). Until the
 * first approval the account is unlinked. The old single `customers_id` column
 * is RETIRED (backfilled into the pivot; no code reads or writes it).
 *
 * @property int         $id
 * @property int|null    $customers_id  retired single-link column (pivot is the source of truth)
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
		'name', 'email', 'phone', 'birth_date', 'password',
		'email_verified_at', 'locked_until', 'status', 'settings',
	];

	/** @var array<int, int>|null memoized linkedCustomerIds() result (per request) */
	private ?array $linkedIdsCache = null;

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
	 * The linked CRM customer via the retired single-link column.
	 *
	 * @deprecated The `customers_id` column is retired - use customers() /
	 *             linkedCustomerIds() (the cus_users_customers pivot) instead.
	 */
	public function customer(): BelongsTo
	{
		return $this->belongsTo(Customer::class, 'customers_id');
	}

	/**
	 * The linked CRM customers (the `cus_users_customers` pivot), in approval
	 * order (pivot id ASC) - the first row is the account's default customer.
	 *
	 * @example  $user->customers->pluck('name')->all()
	 */
	public function customers(): BelongsToMany
	{
		return $this->belongsToMany(Customer::class, 'cus_users_customers', 'cus_users_id', 'customers_id')
			->orderBy('cus_users_customers.id');
	}

	/**
	 * The linked customer ids in approval order (pivot id ASC), [] when the
	 * account is not yet linked. Pivot-only query (no `customers` join), memoized
	 * for the request - the portal calls this on every page render.
	 *
	 * @return array<int, int>
	 *
	 * @example  $ids = $user->linkedCustomerIds();   // [12, 47]
	 */
	public function linkedCustomerIds(): array
	{
		if ($this->linkedIdsCache === null) {
			$this->linkedIdsCache = DB::table('cus_users_customers')
				->where('cus_users_id', $this->id)
				->orderBy('id')
				->pluck('customers_id')
				->map(static fn ($id): int => (int) $id)
				->all();
		}

		return $this->linkedIdsCache;
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
