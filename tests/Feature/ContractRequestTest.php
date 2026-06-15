<?php

namespace Tests\Feature;

use App\Models\ContractRequest;
use App\Models\CustomerUser;
use Tests\FeatureTestCase;

/**
 * The dashboard "Szerződés hozzárendelése" form (ContractRequestController):
 * completes an incomplete pending request (the empty row from a data-less
 * registration) instead of duplicating, else files a new one.
 */
class ContractRequestTest extends FeatureTestCase
{
	public function test_the_form_completes_an_incomplete_pending_request(): void
	{
		$user = CustomerUser::factory()->create(); // verified
		ContractRequest::create(['cus_users_id' => $user->id, 'status' => 'pending']); // empty row

		$this->actingAs($user, 'customer')
			->post(route('contract.request'), ['contract_number' => 'SV-1', 'birth_date' => '1990-01-01'])
			->assertRedirect();

		$this->assertDatabaseCount('cus_contract_requests', 1);
		$this->assertDatabaseHas('cus_contract_requests', [
			'cus_users_id'    => $user->id,
			'contract_number' => 'SV-1',
		]);
	}

	public function test_the_form_adds_a_new_request_when_none_is_incomplete(): void
	{
		$user = CustomerUser::factory()->create();
		ContractRequest::create([
			'cus_users_id'    => $user->id,
			'contract_number' => 'SV-OLD',
			'birth_date'      => '1980-01-01',
			'status'          => 'pending',
		]);

		$this->actingAs($user, 'customer')
			->post(route('contract.request'), ['contract_number' => 'SV-2', 'birth_date' => '1991-02-02'])
			->assertRedirect();

		$this->assertDatabaseCount('cus_contract_requests', 2);
	}
}
