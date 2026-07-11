<?php

namespace Tests\Feature;

use App\Models\CustomerUser;
use App\Models\LoginAttempt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tests\FeatureTestCase;

class PasswordResetTest extends FeatureTestCase
{
	public function test_forgot_page_renders(): void
	{
		$this->get(route('forgot'))->assertOk()->assertSee('Elfelejtett jelszó');
	}

	public function test_requesting_a_link_creates_a_token_for_an_existing_account(): void
	{
		CustomerUser::factory()->create(['email' => 'kis.eva@example.hu']);

		$this->postJson(route('forgot.submit'), ['email' => 'kis.eva@example.hu'])
			->assertOk()->assertJsonPath('redirect', route('forgot'));

		$this->assertDatabaseHas('cus_password_reset_tokens', ['email' => 'kis.eva@example.hu']);
	}

	public function test_requesting_a_link_for_an_unknown_address_still_reports_sent(): void
	{
		$this->postJson(route('forgot.submit'), ['email' => 'nobody@example.hu'])
			->assertOk()->assertJsonPath('redirect', route('forgot'));

		$this->assertDatabaseCount('cus_password_reset_tokens', 0);
	}

	public function test_a_customer_can_reset_their_password_with_a_valid_token(): void
	{
		$user  = CustomerUser::factory()->create(['email' => 'kis.eva@example.hu']);
		$token = Password::broker('customers')->createToken($user);

		$this->postJson(route('password.update'), [
			'token'                 => $token,
			'email'                 => 'kis.eva@example.hu',
			'password'              => 'NewPass123',
			'password_confirmation' => 'NewPass123',
		])->assertOk()->assertJsonPath('redirect', route('login'));

		$this->assertTrue(Hash::check('NewPass123', $user->fresh()->password));
	}

	public function test_an_invalid_token_is_rejected(): void
	{
		CustomerUser::factory()->create(['email' => 'kis.eva@example.hu']);

		$this->postJson(route('password.update'), [
			'token'                 => 'totally-invalid-token',
			'email'                 => 'kis.eva@example.hu',
			'password'              => 'NewPass123',
			'password_confirmation' => 'NewPass123',
		])->assertStatus(422);
	}

	public function test_a_successful_reset_rotates_the_remember_token(): void
	{
		// A captured 180-day remember cookie must stop working after a reset
		// (audit E3-L2).
		$user = CustomerUser::factory()->create(['email' => 'kis.eva@example.hu']);
		$user->setRememberToken('old-remember-token');
		$user->save();

		$token = Password::broker('customers')->createToken($user);
		$this->postJson(route('password.update'), [
			'token'                 => $token,
			'email'                 => 'kis.eva@example.hu',
			'password'              => 'NewPass123',
			'password_confirmation' => 'NewPass123',
		])->assertOk();

		$this->assertNotSame('old-remember-token', $user->fresh()->getRememberToken());
	}

	public function test_a_successful_reset_clears_the_login_attempt_counter(): void
	{
		// Lockout state: 5 rolling-window attempts + a live lock (audit E3-L1).
		$user = CustomerUser::factory()->create([
			'email'        => 'kis.eva@example.hu',
			'locked_until' => now()->addDay(),
		]);
		for ($i = 0; $i < 5; $i++) {
			LoginAttempt::create(['email' => $user->email, 'ip' => '1.2.3.4', 'created_at' => now()]);
		}

		$token = Password::broker('customers')->createToken($user);
		$this->postJson(route('password.update'), [
			'token'                 => $token,
			'email'                 => 'kis.eva@example.hu',
			'password'              => 'NewPass123',
			'password_confirmation' => 'NewPass123',
		])->assertOk();

		// Lock lifted AND the counter cleared with it.
		$this->assertNull($user->fresh()->locked_until);
		$this->assertDatabaseMissing('cus_login_attempts', ['email' => $user->email]);

		// One typo of the new password must NOT relock the account.
		$this->postJson(route('login.submit'), ['login' => $user->email, 'password' => 'WrongPass123'])
			->assertStatus(422);
		$this->assertNull($user->fresh()->locked_until);
	}
}
