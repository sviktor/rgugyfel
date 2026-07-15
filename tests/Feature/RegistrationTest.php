<?php

namespace Tests\Feature;

use App\Models\CustomerUser;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\FeatureTestCase;

class RegistrationTest extends FeatureTestCase
{
	/** A valid, complete payload (contract-number identification path). */
	private function payload(array $overrides = []): array
	{
		return array_merge([
			'lastName'              => 'Kis',
			'firstName'            => 'Éva',
			'email'                => 'kis.eva@example.hu',
			'phone'                => '+36 30 123 4567',
			'birth_date'           => '1990-05-14',
			'contract_number'      => 'SV24-00170',
			'password'             => 'Password123',
			'password_confirmation' => 'Password123',
			'accept'               => true,
		], $overrides);
	}

	public function test_registration_page_renders(): void
	{
		$this->get(route('register'))->assertOk()->assertSee('Regisztráció');
	}

	public function test_valid_registration_creates_unverified_account_and_pending_request(): void
	{
		$response = $this->postJson(route('register.submit'), $this->payload());

		$response->assertOk()->assertJsonPath('redirect', route('register.verify.notice'));

		$this->assertDatabaseHas('cus_users', [
			'email'             => 'kis.eva@example.hu',
			'name'              => 'Kis Éva',
			'email_verified_at' => null,
		]);
		$this->assertDatabaseHas('cus_contract_requests', [
			'contract_number' => 'SV24-00170',
			'status'          => 'pending',
		]);

		// The password is stored hashed, never in plain text.
		$user = CustomerUser::where('email', 'kis.eva@example.hu')->first();
		$this->assertNotSame('Password123', $user->password);
	}

	public function test_registration_stores_the_birth_date_on_the_account_too(): void
	{
		// E3-L3: the DOB must land on cus_users as well (Profil + the
		// add-contract prefill read it), not only on the contract request.
		$this->postJson(route('register.submit'), $this->payload())->assertOk();

		$this->assertDatabaseHas('cus_users', [
			'email'      => 'kis.eva@example.hu',
			'birth_date' => '1990-05-14',
		]);
	}

	public function test_legacy_address_fields_are_ignored(): void
	{
		// Address-based identification was removed. Posting the old zip/city/street
		// keys is harmless (ignored, never stored); the request is created with no
		// identification (it lists as "Adatokra vár" in rgadmin until the contract
		// number + birth date come).
		$this->postJson(route('register.submit'), $this->payload([
			'contract_number' => null,
			'birth_date'      => null,
			'zip'             => '1037',
			'city'            => 'Budapest',
			'street'          => 'Aranyhegyi út 14.',
		]))->assertOk()->assertJsonPath('redirect', route('register.verify.notice'));

		$this->assertDatabaseHas('cus_contract_requests', [
			'contract_number' => null,
			'birth_date'      => null,
			'status'          => 'pending',
		]);
	}

	public function test_registration_succeeds_without_any_identification(): void
	{
		// Identification is optional: a request row is still created (it lists as
		// "Adatokra vár" in rgadmin until the contract number + birth date come).
		$this->postJson(route('register.submit'), $this->payload([
			'contract_number' => null,
			'birth_date'      => null,
		]))->assertOk()->assertJsonPath('redirect', route('register.verify.notice'));

		$this->assertDatabaseHas('cus_users', ['email' => 'kis.eva@example.hu']);
		$this->assertDatabaseHas('cus_contract_requests', [
			'contract_number' => null,
			'birth_date'      => null,
			'status'          => 'pending',
		]);
	}

	public function test_registration_persists_the_notification_optins(): void
	{
		// The two opt-in checkboxes land in cus_users.settings.notify (the same
		// shape /profil?tab=notif reads), so the sign-up choice shows on the
		// profile and gates the promo newsletter in rgadmin.
		$this->postJson(route('register.submit'), $this->payload([
			'notify_outage' => '1',
			'notify_promo'  => '1',
		]))->assertOk();

		$user = CustomerUser::where('email', 'kis.eva@example.hu')->first();
		$this->assertSame(['outage' => true, 'promo' => true], $user->settings['notify']);
	}

	public function test_registration_defaults_notifications_off_when_unchecked(): void
	{
		// Unchecked checkboxes are simply not submitted, so both preferences store
		// as false server-side (the "outage checked" default is only a UI hint).
		$this->postJson(route('register.submit'), $this->payload())->assertOk();

		$user = CustomerUser::where('email', 'kis.eva@example.hu')->first();
		$this->assertSame(['outage' => false, 'promo' => false], $user->settings['notify']);
	}

	public function test_registration_rejects_a_weak_password(): void
	{
		$this->postJson(route('register.submit'), $this->payload([
			'password'              => 'short',
			'password_confirmation' => 'short',
		]))->assertStatus(422)->assertJsonValidationErrors('password');
	}

	public function test_registration_rejects_a_duplicate_email(): void
	{
		CustomerUser::factory()->create(['email' => 'kis.eva@example.hu']);

		$this->postJson(route('register.submit'), $this->payload())
			->assertStatus(422)
			->assertJsonValidationErrors('email');
	}

	public function test_recaptcha_gate_blocks_when_enabled_without_a_token(): void
	{
		config(['recaptcha.enabled' => true, 'recaptcha.site_key' => 'x', 'recaptcha.secret_key' => 'y']);

		$this->postJson(route('register.submit'), $this->payload())
			->assertStatus(422)
			->assertJsonValidationErrors('g-recaptcha-response');

		$this->assertDatabaseCount('cus_users', 0);
	}

	public function test_recaptcha_gate_is_skipped_when_the_config_is_incomplete(): void
	{
		// Enabled but the secret key is missing (audit E3-M2): the captcha must
		// deactivate itself instead of rejecting every submission.
		config(['recaptcha.enabled' => true, 'recaptcha.site_key' => 'x', 'recaptcha.secret_key' => '']);

		$this->postJson(route('register.submit'), $this->payload())
			->assertOk()
			->assertJsonPath('redirect', route('register.verify.notice'));

		$this->assertDatabaseHas('cus_users', ['email' => 'kis.eva@example.hu']);
	}

	public function test_recaptcha_array_token_is_rejected_not_500(): void
	{
		config(['recaptcha.enabled' => true, 'recaptcha.site_key' => 'x', 'recaptcha.secret_key' => 'y']);

		// A crafted array token (g-recaptcha-response[]) must fall back to an
		// empty token -> 422, never a (string)-cast 500 (audit E3-L7).
		$this->postJson(route('register.submit'), $this->payload(['g-recaptcha-response' => ['x']]))
			->assertStatus(422)
			->assertJsonValidationErrors('g-recaptcha-response');

		$this->assertDatabaseCount('cus_users', 0);
	}

	public function test_recaptcha_outage_fails_closed_with_a_clear_message(): void
	{
		config(['recaptcha.enabled' => true, 'recaptcha.site_key' => 'x', 'recaptcha.secret_key' => 'y']);

		// Google siteverify unreachable (audit E3-M1): the gate must fail
		// CLOSED with an honest validation message - never a 500 - and no
		// account may be created.
		Http::fake(function (): void {
			throw new ConnectionException('timeout');
		});

		$this->postJson(route('register.submit'), $this->payload(['g-recaptcha-response' => 'token']))
			->assertStatus(422)
			->assertJsonValidationErrors([
				'g-recaptcha-response' => 'A robot-ellenőrzés átmenetileg nem elérhető.',
			]);

		$this->assertDatabaseCount('cus_users', 0);
	}
}
