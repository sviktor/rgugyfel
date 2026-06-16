<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add a `birth_date` DATE column to cus_users - the account holder's date of
 * birth, entered on the Profil page (registration leaves it on the contract
 * request, not the account). When set it prefills the "Szerződés hozzárendelése"
 * birth_date field. Nullable; cus_users is rgugyfel-owned.
 *
 * Guarded by hasColumn so it is safe on environments where the column may already
 * exist.
 */
return new class extends Migration
{
	public function up(): void
	{
		if (! Schema::hasColumn('cus_users', 'birth_date')) {
			Schema::table('cus_users', function (Blueprint $table) {
				$table->date('birth_date')->nullable()->after('phone');
			});
		}
	}

	public function down(): void
	{
		if (Schema::hasColumn('cus_users', 'birth_date')) {
			Schema::table('cus_users', function (Blueprint $table) {
				$table->dropColumn('birth_date');
			});
		}
	}
};
