<?php

namespace Tests\Feature;

use App\Models\CustomerUser;
use Tests\FeatureTestCase;

/**
 * Login hardening: the per-IP rate limit on POST /login and the staff-disabled
 * account gate (cus_users.status). Both sit on the login path in addition to the
 * per-account brute-force lockout (cus_login_attempts). See AuthController::login
 * + routes/web.php (throttle:10,1). The test cache store is `array` (phpunit.xml),
 * so the rate-limit counter resets between test methods.
 */
class LoginSecurityTest extends FeatureTestCase
{
	public function test_login_is_rate_limited_per_ip(): void
	{
		$payload = ['login' => 'nincs-ilyen@example.com', 'password' => 'whatever'];

		// The first 10 attempts reach the controller (failed login, 422); a
		// non-existent account is never locked (recordFailure returns early).
		for ($i = 0; $i < 10; $i++) {
			$this->postJson(route('login.submit'), $payload)->assertStatus(422);
		}

		// The 11th trips the throttle middleware before the controller runs.
		$this->postJson(route('login.submit'), $payload)->assertStatus(429);
	}

	public function test_a_disabled_account_cannot_log_in_with_the_correct_password(): void
	{
		$user = CustomerUser::factory()->create(['status' => 0]);

		$this->postJson(route('login.submit'), ['login' => $user->email, 'password' => 'Password123'])
			->assertStatus(422)
			->assertJsonFragment(['title' => 'Fiók letiltva']);

		$this->assertGuest('customer');
	}

	public function test_an_active_verified_account_still_logs_in(): void
	{
		$user = CustomerUser::factory()->create();

		$this->postJson(route('login.submit'), ['login' => $user->email, 'password' => 'Password123'])
			->assertOk()
			->assertJson(['redirect' => route('dashboard')]);

		$this->assertAuthenticatedAs($user, 'customer');
	}
}
