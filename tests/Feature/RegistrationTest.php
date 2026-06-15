<?php

namespace Tests\Feature;

use App\Models\CustomerUser;
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
}
