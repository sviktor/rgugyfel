<?php

namespace Tests\Feature;

use App\Models\CustomerUser;
use Illuminate\Support\Facades\DB;
use Tests\FeatureTestCase;

/**
 * The cus_users_customers pivot (multi-customer portal accounts): the migration
 * backfill (retired cus_users.customers_id column -> pivot, idempotent) and the
 * CustomerUser::linkedCustomerIds() / customers() accessors that every portal
 * page resolves the active customer from. The `customers` rows come from the
 * test-only shared-table mirror migration (mc_rg_cp_test).
 */
class CustomerLinkTest extends FeatureTestCase
{
	/**
	 * Re-run the pivot migration's up() (create skipped - table exists; the
	 * idempotent backfill runs again).
	 */
	private function rerunPivotMigration(): void
	{
		$migration = require database_path('migrations/2026_07_11_000001_create_cus_users_customers_table.php');
		$migration->up();
	}

	public function test_the_backfill_copies_column_links_into_the_pivot_once(): void
	{
		$legacy   = CustomerUser::factory()->create(); // customers_id column still set (pre-pivot data)
		$linked   = CustomerUser::factory()->create(); // already pivot-linked
		$unlinked = CustomerUser::factory()->create(); // never linked

		DB::table('cus_users')->where('id', $legacy->id)->update(['customers_id' => 11]);
		DB::table('cus_users_customers')->insert([
			'cus_users_id' => $linked->id,
			'customers_id' => 22,
			'created_at'   => now(),
		]);

		$this->rerunPivotMigration();
		$this->rerunPivotMigration(); // second run must not duplicate anything

		$this->assertDatabaseCount('cus_users_customers', 2);
		$this->assertDatabaseHas('cus_users_customers', ['cus_users_id' => $legacy->id, 'customers_id' => 11]);
		$this->assertDatabaseHas('cus_users_customers', ['cus_users_id' => $linked->id, 'customers_id' => 22]);
		$this->assertSame(
			0,
			DB::table('cus_users_customers')->where('cus_users_id', $unlinked->id)->count(),
		);
	}

	public function test_the_backfill_does_not_duplicate_an_already_pivoted_column_link(): void
	{
		$user = CustomerUser::factory()->create();
		DB::table('cus_users')->where('id', $user->id)->update(['customers_id' => 33]);
		DB::table('cus_users_customers')->insert([
			'cus_users_id' => $user->id,
			'customers_id' => 33,
			'created_at'   => now(),
		]);

		$this->rerunPivotMigration();

		$this->assertDatabaseCount('cus_users_customers', 1);
	}

	public function test_linked_customer_ids_returns_ints_in_pivot_order(): void
	{
		$user = CustomerUser::factory()->create();
		// Approval order: 47 first, 12 second - id order must win over value order.
		DB::table('cus_users_customers')->insert([
			['cus_users_id' => $user->id, 'customers_id' => 47, 'created_at' => now()],
			['cus_users_id' => $user->id, 'customers_id' => 12, 'created_at' => now()],
		]);

		$this->assertSame([47, 12], $user->linkedCustomerIds());
	}

	public function test_linked_customer_ids_is_empty_for_an_unlinked_account(): void
	{
		$user = CustomerUser::factory()->create();

		$this->assertSame([], $user->linkedCustomerIds());
	}

	public function test_the_customers_relation_lists_in_pivot_order(): void
	{
		$user = CustomerUser::factory()->create();
		DB::table('customers')->insert([
			['id' => 1, 'name' => 'Kiss Anna', 'customer_number' => '2026000001', 'status' => 1, 'deleted' => 0],
			['id' => 2, 'name' => 'Nagy Béla', 'customer_number' => '2026000002', 'status' => 1, 'deleted' => 0],
		]);
		DB::table('cus_users_customers')->insert([
			['cus_users_id' => $user->id, 'customers_id' => 2, 'created_at' => now()],
			['cus_users_id' => $user->id, 'customers_id' => 1, 'created_at' => now()],
		]);

		$this->assertSame(['Nagy Béla', 'Kiss Anna'], $user->customers->pluck('name')->all());
	}
}
