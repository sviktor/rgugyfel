<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Drop the address columns (zip/city/street) from cus_contract_requests.
 *
 * The contract-link identification is now ONLY the contract number + the
 * contract holder's birth date - the address-based path was removed (both from
 * registration step 2 and the dashboard form). Each column drop is guarded with
 * Schema::hasColumn, so this is a no-op on a fresh install (where the create
 * migration may have run create-then-drop) and a clean removal on the live
 * shared `mc_rg`.
 */
return new class extends Migration
{
	public function up(): void
	{
		Schema::table('cus_contract_requests', function (Blueprint $table) {
			foreach (['zip', 'city', 'street'] as $column) {
				if (Schema::hasColumn('cus_contract_requests', $column)) {
					$table->dropColumn($column);
				}
			}
		});
	}

	public function down(): void
	{
		Schema::table('cus_contract_requests', function (Blueprint $table) {
			if (! Schema::hasColumn('cus_contract_requests', 'zip')) {
				$table->string('zip', 20)->nullable();
			}
			if (! Schema::hasColumn('cus_contract_requests', 'city')) {
				$table->string('city')->nullable();
			}
			if (! Schema::hasColumn('cus_contract_requests', 'street')) {
				$table->string('street')->nullable();
			}
		});
	}
};
