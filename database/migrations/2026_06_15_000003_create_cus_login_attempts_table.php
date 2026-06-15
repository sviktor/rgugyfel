<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * cus_login_attempts - one row per FAILED portal login, used to count failures
 * inside a rolling window for the brute-force lockout (5 fails / 60 min ->
 * cus_users.locked_until = +1 day) and as a small audit trail.
 *
 * Keyed loosely by the typed identifier (`email`) + `ip`; the (email, created_at)
 * index makes the windowed count cheap. Rows for an account are cleared on a
 * successful login.
 */
return new class extends Migration
{
	public function up(): void
	{
		Schema::create('cus_login_attempts', function (Blueprint $table) {
			$table->id();
			$table->string('email');                 // the identifier the visitor typed
			$table->string('ip', 45)->nullable();
			$table->timestamp('created_at')->nullable();

			$table->index(['email', 'created_at']);
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('cus_login_attempts');
	}
};
