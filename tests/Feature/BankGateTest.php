<?php

namespace Tests\Feature;

use App\Models\CustomerUser;
use Illuminate\Support\Facades\DB;
use Tests\FeatureTestCase;

/**
 * The customer portal must NOT show the bank-transfer details until an operator
 * has verified them in rgadmin (the shared `settings` flag
 * 'content_verify.portal.global.bank'). Until then PortalController::bankDetails()
 * returns null and the dashboard + Számláim hide the bank modal + every
 * "Kifizetem" trigger, so no seed/test account number is ever exposed. The
 * shared `customers` / `settings` tables exist in mc_rg_cp_test via the test-only
 * mirror migration; rows go in with DB::table (the read models have no factories).
 */
class BankGateTest extends FeatureTestCase
{
	/** A portal account linked to one customer, so the pages render real data. */
	private function linkedAccount(): CustomerUser
	{
		$user = CustomerUser::factory()->create();
		DB::table('customers')->insert([
			'id'              => 1,
			'name'            => 'Kiss Anna',
			'customer_number' => '2026000001',
			'address'         => 'Budapest, Teszt u. 1.',
			'status'          => 1,
			'deleted'         => 0,
		]);
		DB::table('cus_users_customers')->insert([
			'cus_users_id' => $user->id,
			'customers_id' => 1,
			'created_at'   => now(),
		]);

		return $user;
	}

	/** Mark the portal bank details verified - what rgadmin's toggle writes. */
	private function verifyBank(): void
	{
		DB::table('settings')->insert(['name' => 'content_verify.portal.global.bank', 'value' => '1']);
	}

	public function test_the_dashboard_hides_the_bank_modal_until_verified(): void
	{
		$user = $this->linkedAccount();

		$this->actingAs($user, 'customer')->get(route('dashboard'))
			->assertOk()
			->assertDontSee('p-modal-bank');

		$this->verifyBank();

		$this->actingAs($user, 'customer')->get(route('dashboard'))
			->assertOk()
			->assertSee('p-modal-bank');
	}

	public function test_the_invoices_page_hides_the_bank_modal_until_verified(): void
	{
		$user = $this->linkedAccount();

		$this->actingAs($user, 'customer')->get(route('invoices'))
			->assertOk()
			->assertDontSee('p-modal-bank');

		$this->verifyBank();

		$this->actingAs($user, 'customer')->get(route('invoices'))
			->assertOk()
			->assertSee('p-modal-bank');
	}
}
