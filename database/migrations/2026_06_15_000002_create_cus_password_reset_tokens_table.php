<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * cus_password_reset_tokens - the standard Laravel password-reset token store,
 * prefixed `cus_` and bound to the `customers` password broker (config/auth.php).
 * The portal sends its OWN branded reset mail (via the shared email_templates),
 * but reuses Laravel's secure token hashing + throttle by going through the broker.
 */
return new class extends Migration
{
	public function up(): void
	{
		Schema::create('cus_password_reset_tokens', function (Blueprint $table) {
			$table->string('email')->primary();
			$table->string('token');
			$table->timestamp('created_at')->nullable();
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('cus_password_reset_tokens');
	}
};
