<?php

namespace Tests\Unit;

use App\Models\CustomerUser;
use Tests\TestCase;

/**
 * Pure-logic tests for the CustomerUser account model (no DB).
 */
class CustomerUserTest extends TestCase
{
	public function test_is_locked_is_true_only_for_a_future_lock(): void
	{
		$this->assertFalse((new CustomerUser())->isLocked());
		$this->assertFalse((new CustomerUser(['locked_until' => now()->subDay()]))->isLocked());
		$this->assertTrue((new CustomerUser(['locked_until' => now()->addDay()]))->isLocked());
	}

	public function test_has_verified_email_reflects_the_timestamp(): void
	{
		$this->assertFalse((new CustomerUser())->hasVerifiedEmail());
		$this->assertTrue((new CustomerUser(['email_verified_at' => now()]))->hasVerifiedEmail());
	}
}
