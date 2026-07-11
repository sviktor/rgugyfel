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

	public function test_the_notice_page_shows_an_email_input_when_the_session_is_empty(): void
	{
		// Expired session (audit E3-M3): the resend form must offer a visible
		// e-mail field instead of a guaranteed-invalid empty hidden value.
		$this->get(route('register.verify.notice'))
			->assertOk()
			->assertSee('type="email"', false)
			->assertSee('name="email"', false);
	}

	public function test_resend_reports_a_missing_email_as_a_json_validation_error(): void
	{
		// The data-auth-form AJAX path: the validation error surfaces as 422
		// JSON (shown in the ptAlert lightbox) - not a silently dropped redirect.
		$this->postJson(route('verification.resend'), [])
			->assertStatus(422)
			->assertJsonValidationErrors('email');
	}

	public function test_resend_hands_back_a_json_redirect_for_the_ajax_form(): void
	{
		$this->postJson(route('verification.resend'), ['email' => 'nobody@example.hu'])
			->assertOk()
			->assertJsonPath('redirect', route('register.verify.notice'));
	}
}
