<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * cus_users - the Royal Telekom customer-portal LOGIN accounts (rgugyfel-owned).
 *
 * Separate from the shared CRM `customers` table (owned by rgadmin): a portal
 * account is created by self-registration and is only LINKED to a `customers`
 * row once a staff member approves the customer's contract request (see
 * `cus_contract_requests`). Until then `customers_id` stays NULL.
 *
 * The `customers_id` link is a LOGICAL relationship (resolved in Eloquent), not
 * a DB foreign key - rgugyfel never migrates the cross-app `customers` table and
 * we avoid a migrate-ordering dependency on it.
 */
return new class extends Migration
{
	public function up(): void
	{
		Schema::create('cus_users', function (Blueprint $table) {
			$table->id();
			$table->unsignedBigInteger('customers_id')->nullable(); // linked CRM customer (set on staff approval)
			$table->string('name');
			$table->string('email')->unique();                      // the login identifier
			$table->string('phone', 50)->nullable();
			$table->string('password');
			$table->timestamp('email_verified_at')->nullable();
			$table->dateTime('locked_until')->nullable();           // brute-force lockout end (NULL = not locked)
			$table->unsignedTinyInteger('status')->default(1);      // 1 = active, 0 = disabled
			$table->rememberToken();
			$table->timestamps();

			$table->index('customers_id');
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('cus_users');
	}
};
