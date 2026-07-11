<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Create `cus_users_customers` - the portal-account <-> CRM-customer pivot.
 * One portal login (cus_users) may be linked to N customers (a relative's or a
 * second property's service); rgadmin's approval INSERTS a link here instead of
 * overwriting the retired single `cus_users.customers_id` column.
 *
 * Columns: cus_users_id (FK, cascade on account delete) + customers_id (LOGICAL
 * cross-app link, no FK - the cus_* cluster pattern) + created_at (filled by
 * code). Linked order = id ASC (approval order); "first linked" = lowest id.
 *
 * Guarded by hasTable: rgadmin ships a twin guarded migration (its `mc_rg_test`
 * DB + fresh envs need the table too), so the run order between the two apps
 * must not matter in the shared `mc_rg`. rgugyfel OWNS the schema - down()
 * drops here, never in the rgadmin twin.
 *
 * The backfill copies every existing `cus_users.customers_id` link into the
 * pivot. Idempotent (INSERT IGNORE on the unique pair) and re-runnable; it does
 * NOT null the old column (the column is retired in code, physical drop is a
 * later migration).
 */
return new class extends Migration
{
	public function up(): void
	{
		if (! Schema::hasTable('cus_users_customers')) {
			Schema::create('cus_users_customers', function (Blueprint $table) {
				$table->id();
				$table->unsignedBigInteger('cus_users_id');
				$table->unsignedBigInteger('customers_id');
				$table->dateTime('created_at')->nullable();

				$table->unique(['cus_users_id', 'customers_id'], 'cus_users_customers_uq');
				$table->index('customers_id');

				$table->foreign('cus_users_id')->references('id')->on('cus_users')->cascadeOnDelete();
			});
		}

		// Backfill the single-column links into the pivot (idempotent, order-free).
		DB::statement(
			'INSERT IGNORE INTO cus_users_customers (cus_users_id, customers_id, created_at)'
			. ' SELECT id, customers_id, NOW() FROM cus_users WHERE customers_id IS NOT NULL',
		);
	}

	public function down(): void
	{
		Schema::dropIfExists('cus_users_customers');
	}
};
