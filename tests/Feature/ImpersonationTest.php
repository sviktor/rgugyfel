<?php

namespace Tests\Feature;

use App\Models\CustomerUser;
use Illuminate\Support\Facades\DB;
use Tests\FeatureTestCase;

/**
 * Operator impersonation ("Belépés az ügyfélkapuba"): the public accept route
 * consumes a single-use, short-lived DB token minted by rgadmin and logs the
 * operator in as the linked cus_users account; the exit route leaves the mode.
 * The `cus_impersonation_tokens` table is created in the test DB by the migration
 * (rgugyfel owns the schema). Runs against the portal test database.
 */
class ImpersonationTest extends FeatureTestCase
{
	/** Insert a token row and return the RAW token (only its sha256 hash is stored). */
	private function makeToken(CustomerUser $user, ?string $expiresAt = null, ?string $usedAt = null): string
	{
		$raw = 'tok' . bin2hex(random_bytes(20));
		DB::table('cus_impersonation_tokens')->insert([
			'cus_users_id'   => $user->id,
			'token'          => hash('sha256', $raw),
			'admin_users_id' => 7,
			'ip'             => '127.0.0.1',
			'expires_at'     => $expiresAt ?? now()->addMinutes(2),
			'used_at'        => $usedAt,
			'created_at'     => now(),
		]);

		return $raw;
	}

	public function test_a_valid_token_logs_in_and_marks_the_operator_session(): void
	{
		$user = CustomerUser::factory()->create();
		$raw  = $this->makeToken($user);

		$this->get(route('imperson.accept', ['token' => $raw]))
			->assertRedirect(route('dashboard'))
			->assertSessionHas('impersonation', true);

		$this->assertAuthenticatedAs($user, 'customer');
		$this->assertNotNull(DB::table('cus_impersonation_tokens')->where('cus_users_id', $user->id)->value('used_at'));
	}

	public function test_a_token_is_single_use(): void
	{
		$user = CustomerUser::factory()->create();
		$raw  = $this->makeToken($user);

		$this->get(route('imperson.accept', ['token' => $raw]))->assertRedirect(route('dashboard'));

		// A second hit with the same (now used) token is refused back to login.
		$this->get(route('imperson.accept', ['token' => $raw]))->assertRedirect(route('login'));
	}

	public function test_an_expired_token_is_refused(): void
	{
		$user = CustomerUser::factory()->create();
		$raw  = $this->makeToken($user, expiresAt: now()->subMinute());

		$this->get(route('imperson.accept', ['token' => $raw]))->assertRedirect(route('login'));
		$this->assertGuest('customer');
	}

	public function test_an_unknown_token_is_refused(): void
	{
		$this->get(route('imperson.accept', ['token' => 'does-not-exist']))->assertRedirect(route('login'));
		$this->assertGuest('customer');
	}

	public function test_exit_logs_out_and_returns_to_login(): void
	{
		$user = CustomerUser::factory()->create();

		$this->actingAs($user, 'customer')
			->withSession(['impersonation' => true, 'impersonator_admin_id' => 7])
			->post(route('imperson.exit'))
			->assertRedirect(route('login'));

		$this->assertGuest('customer');
	}
}
