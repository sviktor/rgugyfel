<?php

namespace Tests\Feature;

use App\Models\CustomerUser;
use Illuminate\Support\Facades\URL;
use Tests\FeatureTestCase;

class EmailVerificationTest extends FeatureTestCase
{
	private function signedUrl(CustomerUser $user, ?string $hash = null): string
	{
		return URL::temporarySignedRoute('verification.verify', now()->addDay(), [
			'id'   => $user->id,
			'hash' => $hash ?? sha1($user->email),
		]);
	}

	public function test_a_valid_signed_link_verifies_the_account(): void
	{
		$user = CustomerUser::factory()->unverified()->create();

		$this->get($this->signedUrl($user))
			->assertOk()
			->assertSee('E-mail cím megerősítve');

		$this->assertNotNull($user->fresh()->email_verified_at);
	}

	public function test_a_tampered_link_does_not_verify(): void
	{
		$user = CustomerUser::factory()->unverified()->create();

		// Valid signature but wrong hash -> "invalid" result, no verification.
		$this->get($this->signedUrl($user, 'wronghash'))
			->assertOk()
			->assertSee('A megerősítés nem sikerült');

		$this->assertNull($user->fresh()->email_verified_at);
	}

	public function test_an_unsigned_link_is_rejected(): void
	{
		$user = CustomerUser::factory()->unverified()->create();

		$this->get(route('verification.verify', ['id' => $user->id, 'hash' => sha1($user->email)]))
			->assertOk()
			->assertSee('A megerősítés nem sikerült');

		$this->assertNull($user->fresh()->email_verified_at);
	}

	public function test_resend_is_always_ok_and_does_not_leak_existence(): void
	{
		$this->post(route('verification.resend'), ['email' => 'nobody@example.hu'])
			->assertRedirect(route('register.verify.notice'));
	}
}
