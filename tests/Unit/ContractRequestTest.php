<?php

namespace Tests\Unit;

use App\Models\ContractRequest;
use Tests\TestCase;

/**
 * Pure-logic tests for the ContractRequest model (no DB). A request is
 * "complete" (ready for staff approval) only with BOTH a contract number and a
 * birth date; the status label is derived from that.
 */
class ContractRequestTest extends TestCase
{
	public function test_is_complete_requires_both_contract_number_and_birth_date(): void
	{
		$this->assertFalse((new ContractRequest())->isComplete());
		$this->assertFalse((new ContractRequest(['contract_number' => 'SV-1']))->isComplete());
		$this->assertFalse((new ContractRequest(['birth_date' => '1990-01-01']))->isComplete());
		$this->assertFalse((new ContractRequest(['contract_number' => '   ', 'birth_date' => '1990-01-01']))->isComplete());
		$this->assertTrue((new ContractRequest(['contract_number' => 'SV-1', 'birth_date' => '1990-01-01']))->isComplete());
	}

	public function test_status_label_reflects_completeness(): void
	{
		$this->assertSame('Adatok megadására vár', (new ContractRequest())->statusLabel());
		$this->assertSame(
			'Jóváhagyásra vár',
			(new ContractRequest(['contract_number' => 'SV-1', 'birth_date' => '1990-01-01']))->statusLabel(),
		);
	}
}
