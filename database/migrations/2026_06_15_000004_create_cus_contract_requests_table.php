<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * cus_contract_requests - pending "link this contract to my account" requests.
 *
 * Created BOTH at registration (the contract number + birth date, OR the full
 * address, the visitor gives in step 2) AND by the dashboard "Szerződés
 * hozzárendelése" form. A staff member later approves each request in rgadmin,
 * which sets `customers_id` here (and on cus_users) - until then `status` stays
 * `pending`. No automatic DB matching happens at registration time.
 *
 * `customers_id` is a LOGICAL link (no cross-app DB foreign key); `cus_users_id`
 * is a real FK to the same-app cus_users table.
 */
return new class extends Migration
{
	public function up(): void
	{
		Schema::create('cus_contract_requests', function (Blueprint $table) {
			$table->id();
			$table->unsignedBigInteger('cus_users_id');
			$table->string('contract_number', 50)->nullable(); // e.g. SV24-00170 (legacy rental format)
			$table->date('birth_date')->nullable();
			$table->string('zip', 20)->nullable();             // address path (alternative to contract number)
			$table->string('city')->nullable();
			$table->string('street')->nullable();
			$table->text('note')->nullable();
			$table->string('status', 20)->default('pending');  // pending | approved | rejected
			$table->unsignedBigInteger('customers_id')->nullable(); // set on staff approval
			$table->dateTime('reviewed_at')->nullable();
			$table->timestamps();

			$table->index(['cus_users_id', 'status']);
			$table->index('customers_id');

			$table->foreign('cus_users_id')->references('id')->on('cus_users')->cascadeOnDelete();
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('cus_contract_requests');
	}
};
