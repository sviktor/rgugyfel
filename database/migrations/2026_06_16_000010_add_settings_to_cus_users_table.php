<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add a `settings` JSON column to cus_users - a per-account store for portal UI
 * preferences (currently the Profil -> Értesítések notification toggles). Slim
 * and nullable; empty means "use the defaults".
 *
 * Guarded by hasColumn so it is safe on environments where the column may already
 * exist. cus_users is rgugyfel-owned (see the create migration).
 */
return new class extends Migration
{
	public function up(): void
	{
		if (! Schema::hasColumn('cus_users', 'settings')) {
			Schema::table('cus_users', function (Blueprint $table) {
				$table->json('settings')->nullable()->after('status'); // portal UI prefs (notification toggles, ...)
			});
		}
	}

	public function down(): void
	{
		if (Schema::hasColumn('cus_users', 'settings')) {
			Schema::table('cus_users', function (Blueprint $table) {
				$table->dropColumn('settings');
			});
		}
	}
};
