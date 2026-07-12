<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create `cus_impersonation_tokens` - the short-lived, one-time login tokens that
 * let an rgadmin operator open THIS portal logged in as a given cus_users account
 * ("Belépés az ügyfélkapuba"). The two apps use different APP_KEYs, so a Laravel
 * signed URL cannot be validated cross-app; instead rgadmin (which shares the
 * `mc_rg` DB) MINTS a row here and redirects the operator to the accept route,
 * which consumes it.
 *
 * Columns: cus_users_id (FK, cascade on account delete), token (the sha256 HASH
 * of the raw URL token - a DB leak never yields a usable link), admin_users_id
 * (the operator, a LOGICAL cross-app id, no FK - audit), ip (nullable),
 * expires_at (~2 min) + used_at (nullable = single-use). No updated_at.
 *
 * rgugyfel OWNS the schema - down() drops here. rgadmin ships a twin guarded
 * `*_if_absent` migration (its `mc_rg_test` DB + fresh envs need the table too),
 * so the run order between the two apps must not matter in the shared `mc_rg`.
 */
return new class extends Migration
{
	public function up(): void
	{
		if (! Schema::hasTable('cus_impersonation_tokens')) {
			Schema::create('cus_impersonation_tokens', function (Blueprint $table) {
				$table->id();
				$table->unsignedBigInteger('cus_users_id');
				$table->string('token', 64)->unique(); // sha256 hex of the raw URL token
				$table->unsignedBigInteger('admin_users_id')->nullable(); // operator (cross-app id, no FK)
				$table->string('ip', 45)->nullable();
				$table->dateTime('expires_at');
				$table->dateTime('used_at')->nullable();
				$table->dateTime('created_at')->nullable();

				$table->index('expires_at');
				$table->foreign('cus_users_id')->references('id')->on('cus_users')->cascadeOnDelete();
			});
		}
	}

	public function down(): void
	{
		Schema::dropIfExists('cus_impersonation_tokens');
	}
};
