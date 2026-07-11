<?php

namespace Tests\Feature;

use App\Models\CustomerUser;
use Illuminate\Support\Facades\DB;
use Tests\FeatureTestCase;

/**
 * Customer-number login over the cus_users_customers pivot (E3-L6): the typed
 * number resolves through the PIVOT-linked customer; with several candidate
 * accounts the PASSWORD picks the right one, and a no-match failure books to
 * the typed string instead of inflating an arbitrary account's lockout. The
 * `customers` rows come from the test-only mirror migration (mc_rg_cp_test).
 */
class LoginByCustomerNumberTest extends FeatureTestCase
{
	private const NUMBER = '2026000042';

	/** Seed the numbered customer + link the given accounts to it (pivot). */
	private function linkAccounts(CustomerUser ...$users): void
	{
		DB::table('customers')->insert([
			'id'              => 42,
			'name'            => 'Kiss Anna',
			'customer_number' => self::NUMBER,
			'status'          => 1,
			'deleted'         => 0,
		]);
		foreach ($users as $user) {
			DB::table('cus_users_customers')->insert([
				'cus_users_id' => $user->id,
				'customers_id' => 42,
				'created_at'   => now(),
			]);
		}
	}

	public function test_a_single_linked_account_logs_in_with_the_customer_number(): void
	{
		$user = CustomerUser::factory()->create();
		$this->linkAccounts($user);

		$this->postJson(route('login.submit'), [
			'login'    => self::NUMBER,
			'password' => 'Password123',
		])->assertOk()->assertJsonPath('redirect', route('dashboard'));

		$this->assertAuthenticatedAs($user, 'customer');
	}

	public function test_the_password_picks_the_matching_account_of_several(): void
	{
		$first  = CustomerUser::factory()->create(); // password Password123
		$second = CustomerUser::factory()->create(['password' => 'MasikJelszo99']);
		$this->linkAccounts($first, $second);

		$this->postJson(route('login.submit'), [
			'login'    => self::NUMBER,
			'password' => 'MasikJelszo99',
		])->assertOk()->assertJsonPath('redirect', route('dashboard'));

		$this->assertAuthenticatedAs($second, 'customer');
	}

	public function test_a_no_match_failure_books_to_the_typed_string_not_an_account(): void
	{
		$first  = CustomerUser::factory()->create();
		$second = CustomerUser::factory()->create(['password' => 'MasikJelszo99']);
		$this->linkAccounts($first, $second);

		$this->postJson(route('login.submit'), [
			'login'    => self::NUMBER,
			'password' => 'RosszJelszo11',
		])->assertStatus(422);

		$this->assertGuest('customer');
		// The attempt row carries the typed identifier; no account gets locked
		// (or even counted) for someone else's failed guess.
		$this->assertDatabaseHas('cus_login_attempts', ['email' => self::NUMBER]);
		$this->assertDatabaseMissing('cus_login_attempts', ['email' => $first->email]);
		$this->assertDatabaseMissing('cus_login_attempts', ['email' => $second->email]);
	}

	public function test_a_locked_matching_candidate_is_still_blocked_by_the_lockout(): void
	{
		$first  = CustomerUser::factory()->create();
		$locked = CustomerUser::factory()->locked()->create(['password' => 'MasikJelszo99']);
		$this->linkAccounts($first, $locked);

		$this->postJson(route('login.submit'), [
			'login'    => self::NUMBER,
			'password' => 'MasikJelszo99',
		])->assertStatus(422)->assertJsonPath('title', 'Fiók átmenetileg zárolva');

		$this->assertGuest('customer');
	}

	public function test_an_unknown_customer_number_fails_without_lockout_side_effects(): void
	{
		CustomerUser::factory()->create();

		$this->postJson(route('login.submit'), [
			'login'    => '2026999999',
			'password' => 'Password123',
		])->assertStatus(422);

		$this->assertGuest('customer');
		$this->assertDatabaseHas('cus_login_attempts', ['email' => '2026999999']);
	}
}
