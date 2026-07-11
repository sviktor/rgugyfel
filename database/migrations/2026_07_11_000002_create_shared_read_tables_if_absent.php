<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * TEST-ONLY minimal mirrors of the shared rgadmin-owned tables the portal READS
 * (`customers`, `invoices`, `payments`, `subscriptions`) - the reverse of
 * rgadmin's `*_create_cus_portal_tables_if_absent` pattern. The portal test DB
 * (`mc_rg_cp_test`) is built by rgugyfel migrations alone, so feature tests that
 * link an account to a customer (customer-switcher, invoice ownership, customer-
 * number login) need these tables to exist.
 *
 * DOUBLE guard: runs only when the database name ends in `_test` (mirroring the
 * tests/TestCase.php safety guard), then per-table hasTable. In the shared
 * `mc_rg` and on any fresh non-test environment this is a NO-OP - rgadmin's
 * authoritative (unguarded) migrations own these schemas and must never collide
 * with a mirror.
 *
 * Column set = ONLY what the portal read layer touches (WebInvoices,
 * WebContracts, Subscription/Invoice/Customer models, AuthController lookup).
 * Never extend beyond that; the authoritative schema lives in rgadmin. Keep
 * test `subscriptions` rows EMPTY: a non-empty row would trigger eager loads on
 * `subscriptions_products`/`products`/`prop_*`, which are NOT mirrored - so
 * linked-account tests may render the dashboard + /szamlak pages, but not
 * /szerzodeseim (it would also need `product_listings`).
 *
 * down() is a no-op: rgadmin owns these schemas; a mirror must never drop them.
 */
return new class extends Migration
{
	public function up(): void
	{
		if (! str_ends_with((string) DB::connection()->getDatabaseName(), '_test')) {
			return;
		}

		if (! Schema::hasTable('customers')) {
			Schema::create('customers', function (Blueprint $table) {
				$table->id();
				$table->string('customer_number', 20)->nullable()->unique();
				$table->string('name', 255);
				$table->string('address', 255)->nullable();
				$table->string('email', 200)->nullable();
				$table->json('customer_data')->nullable();
				$table->unsignedTinyInteger('status')->default(1);
				$table->unsignedTinyInteger('deleted')->default(0);
			});
		}

		if (! Schema::hasTable('invoices')) {
			Schema::create('invoices', function (Blueprint $table) {
				$table->id();
				$table->unsignedBigInteger('customers_id')->nullable();
				$table->string('invoice_number', 64)->nullable();
				$table->string('invoice_kind', 12)->default('normal');
				$table->unsignedBigInteger('corrects_invoice_id')->nullable();
				$table->string('payable_type', 32)->nullable();
				$table->unsignedBigInteger('payable_id')->nullable();
				$table->string('contract_number', 64)->nullable();
				$table->date('issue_date')->nullable();
				$table->date('due_date')->nullable();
				$table->date('period_start')->nullable();
				$table->date('period_end')->nullable();
				$table->decimal('net', 14, 2)->nullable();
				$table->decimal('vat', 14, 2)->nullable();
				$table->decimal('gross', 14, 2)->nullable();
				$table->unsignedTinyInteger('paid')->default(0);
				$table->string('buyer_name')->nullable();
				$table->string('buyer_tax_number', 32)->nullable();
				$table->string('buyer_customer_number', 64)->nullable();
				$table->string('buyer_address')->nullable();
				$table->string('pdf_path')->nullable();
				$table->unsignedTinyInteger('has_pdf')->default(0);
				$table->json('xml_data')->nullable();
				$table->unsignedTinyInteger('deleted')->default(0);
			});
		}

		if (! Schema::hasTable('payments')) {
			Schema::create('payments', function (Blueprint $table) {
				$table->id();
				$table->string('payable_type', 20)->nullable();
				$table->unsignedBigInteger('payable_id')->nullable();
				$table->string('kind', 12);
				$table->date('period');
				$table->integer('amount')->default(0);
			});
		}

		if (! Schema::hasTable('subscriptions')) {
			Schema::create('subscriptions', function (Blueprint $table) {
				$table->id();
				$table->unsignedBigInteger('customers_id')->nullable();
				$table->unsignedBigInteger('properties_id')->nullable();
				$table->string('subscription_number', 20)->nullable();
				$table->string('contract_number', 64)->nullable();
				$table->decimal('monthly_fee', 12, 2)->nullable();
				$table->date('contract_date')->nullable();
				$table->date('service_start')->nullable();
				$table->unsignedSmallInteger('loyalty_months')->nullable();
				$table->unsignedTinyInteger('status')->default(1);
				$table->unsignedTinyInteger('deleted')->default(0);
			});
		}
	}

	public function down(): void
	{
		// Intentionally no-op: rgadmin owns these schemas; never drop a mirror.
	}
};
