<?php

namespace Tests\Feature;

use App\Models\CustomerUser;
use Tests\FeatureTestCase;

class LoginTest extends FeatureTestCase
{
	public function test_login_page_renders(): void
	{
		$this->get(route('login'))->assertOk()->assertSee('Belépés');
	}

	public function test_a_verified_customer_can_log_in(): void
	{
		$user = CustomerUser::factory()->create(['email' => 'kis.eva@example.hu']);

		$this->postJson(route('login.submit'), [
			'login'    => 'kis.eva@example.hu',
			'password' => 'Password123',
			'remember' => true,
		])->assertOk()->assertJsonPath('redirect', route('dashboard'));

		$this->assertAuthenticatedAs($user, 'customer');
		$this->assertDatabaseCount('cus_login_attempts', 0);
	}

	public function test_an_unverified_customer_is_routed_to_the_verify_notice(): void
	{
		CustomerUser::factory()->unverified()->create(['email' => 'kis.eva@example.hu']);

		$this->postJson(route('login.submit'), [
			'login'    => 'kis.eva@example.hu',
			'password' => 'Password123',
		])->assertOk()->assertJsonPath('redirect', route('register.verify.notice'));

		$this->assertGuest('customer');
	}

	public function test_wrong_password_is_rejected_and_recorded(): void
	{
		CustomerUser::factory()->create(['email' => 'kis.eva@example.hu']);

		$this->postJson(route('login.submit'), [
			'login'    => 'kis.eva@example.hu',
			'password' => 'WrongPass123',
		])->assertStatus(422);

		$this->assertGuest('customer');
		$this->assertDatabaseCount('cus_login_attempts', 1);
	}

	public function test_five_failures_lock_the_account_for_a_day(): void
	{
		$user = CustomerUser::factory()->create(['email' => 'kis.eva@example.hu']);

		for ($i = 0; $i < 5; $i++) {
			$this->postJson(route('login.submit'), [
				'login'    => 'kis.eva@example.hu',
				'password' => 'WrongPass123',
			])->assertStatus(422);
		}

		$this->assertNotNull($user->fresh()->locked_until);

		// Even the correct password is now blocked by the lockout.
		$this->postJson(route('login.submit'), [
			'login'    => 'kis.eva@example.hu',
			'password' => 'Password123',
		])->assertStatus(422);

		$this->assertGuest('customer');
	}
}
