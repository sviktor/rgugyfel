<?php

namespace Tests\Feature;

use App\Models\ContractRequest;
use App\Models\CustomerUser;
use Tests\FeatureTestCase;

/**
 * The "Szerződés hozzárendelése" form (ContractRequestController). Posts via the
 * portal AJAX mechanism: a success hands back a `redirect` target (the page then
 * reloads + the pt_alert flash fires the modal), a validation error returns 422.
 * It completes an incomplete pending request (the empty row from a data-less
 * registration) instead of duplicating, else files a new one. The card also
 * lists the account's pending requests.
 */
class ContractRequestTest extends FeatureTestCase
{
	public function test_the_form_completes_an_incomplete_pending_request(): void
	{
		$user = CustomerUser::factory()->create(); // verified
		ContractRequest::create(['cus_users_id' => $user->id, 'status' => 'pending']); // empty row

		$this->actingAs($user, 'customer')
			->postJson(route('contract.request'), ['contract_number' => 'SV-1', 'birth_date' => '1990-01-01'])
			->assertOk()
			->assertJsonPath('redirect', route('dashboard'));

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
			->postJson(route('contract.request'), ['contract_number' => 'SV-2', 'birth_date' => '1991-02-02'])
			->assertOk()
			->assertJsonPath('redirect', route('dashboard'));

		$this->assertDatabaseCount('cus_contract_requests', 2);
	}

	public function test_it_returns_to_the_szerzodeseim_page_when_posted_from_there(): void
	{
		$user = CustomerUser::factory()->create();

		$this->actingAs($user, 'customer')
			->postJson(route('contract.request'), [
				'contract_number' => 'SV-3',
				'birth_date'      => '1992-03-03',
				'return_to'       => route('plans'),
			])
			->assertOk()
			->assertJsonPath('redirect', route('plans'));
	}

	public function test_an_invalid_submit_returns_validation_errors(): void
	{
		$user = CustomerUser::factory()->create();

		$this->actingAs($user, 'customer')
			->postJson(route('contract.request'), ['contract_number' => '', 'birth_date' => ''])
			->assertStatus(422)
			->assertJsonValidationErrors(['contract_number', 'birth_date']);

		$this->assertDatabaseCount('cus_contract_requests', 0);
	}

	public function test_a_pending_request_is_listed_in_the_card(): void
	{
		$user = CustomerUser::factory()->create();
		ContractRequest::create([
			'cus_users_id'    => $user->id,
			'contract_number' => 'SV-PEND-1',
			'birth_date'      => '1985-03-03',
			'status'          => 'pending',
		]);

		$this->actingAs($user, 'customer')
			->get(route('dashboard'))
			->assertOk()
			->assertSee('Függő kérelmek')
			->assertSee('SV-PEND-1');
	}

	public function test_an_incomplete_pending_request_is_not_listed_in_the_card(): void
	{
		$user = CustomerUser::factory()->create();
		ContractRequest::create(['cus_users_id' => $user->id, 'status' => 'pending']); // empty / incomplete

		$this->actingAs($user, 'customer')
			->get(route('dashboard'))
			->assertOk()
			->assertDontSee('Függő kérelmek');
	}
}
