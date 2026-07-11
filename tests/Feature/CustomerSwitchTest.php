<?php

namespace Tests\Feature;

use App\Models\CustomerUser;
use App\Support\WebInvoices;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\FeatureTestCase;

/**
 * The multi-customer account's ACTIVE-customer resolution + the topbar customer
 * switcher (PortalController::currentCustomerId / switchCustomer) and the
 * any-linked-customer invoice download ownership (WebInvoices::find). The
 * shared tables (customers, invoices) exist in mc_rg_cp_test via the test-only
 * mirror migration; rows go in with DB::table (the read models have no
 * fillable/factories). The bank modal's Közlemény reference (bankRef) is the
 * active customer's number - the tests read the active context off that.
 */
class CustomerSwitchTest extends FeatureTestCase
{
	/** Seed a customers row the portal can read. */
	private function makeCustomer(int $id, string $name, string $number): void
	{
		DB::table('customers')->insert([
			'id'              => $id,
			'name'            => $name,
			'customer_number' => $number,
			'address'         => 'Budapest, Teszt u. ' . $id . '.',
			'status'          => 1,
			'deleted'         => 0,
		]);
	}

	/** Link an account to a customer (pivot row, insertion order = approval order). */
	private function link(CustomerUser $user, int $customerId): void
	{
		DB::table('cus_users_customers')->insert([
			'cus_users_id' => $user->id,
			'customers_id' => $customerId,
			'created_at'   => now(),
		]);
	}

	/** An account linked to customer 1 (Kiss Anna) and customer 2 (Nagy Béla), in this order. */
	private function makeTwoCustomerAccount(): CustomerUser
	{
		$user = CustomerUser::factory()->create();
		$this->makeCustomer(1, 'Kiss Anna', '2026000001');
		$this->makeCustomer(2, 'Nagy Béla', '2026000002');
		$this->link($user, 1);
		$this->link($user, 2);

		return $user;
	}

	public function test_an_unlinked_account_keeps_the_gating(): void
	{
		$user = CustomerUser::factory()->create();

		$this->actingAs($user, 'customer')->get(route('dashboard'))
			->assertOk()
			->assertDontSee('p-cswitch');
		$this->actingAs($user, 'customer')->get(route('invoices'))
			->assertRedirect(route('dashboard'));
	}

	public function test_a_single_customer_account_shows_no_switcher(): void
	{
		$user = CustomerUser::factory()->create();
		$this->makeCustomer(1, 'Kiss Anna', '2026000001');
		$this->link($user, 1);

		$this->actingAs($user, 'customer')->get(route('dashboard'))
			->assertOk()
			->assertDontSee('p-cswitch');
	}

	public function test_the_switcher_lists_both_linked_customers(): void
	{
		$user = $this->makeTwoCustomerAccount();

		$this->actingAs($user, 'customer')->get(route('dashboard'))
			->assertOk()
			->assertSee('p-cswitch')
			->assertSee('Kiss Anna')
			->assertSee('Nagy Béla');
	}

	public function test_the_default_active_customer_is_the_first_linked(): void
	{
		$user = $this->makeTwoCustomerAccount();

		// The bank modal's Közlemény reference carries the ACTIVE customer's number.
		$this->actingAs($user, 'customer')->get(route('dashboard'))
			->assertOk()
			->assertSee("bankRef: '2026000001'", false);
	}

	public function test_switching_stores_the_session_and_changes_the_context(): void
	{
		$user = $this->makeTwoCustomerAccount();

		$this->actingAs($user, 'customer')
			->post(route('customer.switch'), ['customer_id' => 2])
			->assertRedirect()
			->assertSessionHas('active_customer_id', 2);

		$this->actingAs($user, 'customer')->get(route('dashboard'))
			->assertOk()
			->assertSee("bankRef: '2026000002'", false);
	}

	public function test_switching_to_a_foreign_customer_is_forbidden(): void
	{
		$user = $this->makeTwoCustomerAccount();
		$this->makeCustomer(3, 'Idegen Cég Kft.', '2026000003');

		$this->actingAs($user, 'customer')
			->post(route('customer.switch'), ['customer_id' => 3])
			->assertForbidden();

		$this->assertNull(session('active_customer_id'));
	}

	public function test_a_stale_session_customer_falls_back_to_the_first_linked(): void
	{
		$user = $this->makeTwoCustomerAccount();

		// E.g. the link was removed since, or another account's leftover value.
		$this->actingAs($user, 'customer')
			->withSession(['active_customer_id' => 999])
			->get(route('dashboard'))
			->assertOk()
			->assertSee("bankRef: '2026000001'", false);
	}

	public function test_find_resolves_any_linked_customers_invoice(): void
	{
		$this->makeInvoice(101, 1);
		$this->makeInvoice(102, 2);
		$this->makeInvoice(103, 3);

		$this->assertNotNull(WebInvoices::find(101, [1, 2]));
		$this->assertNotNull(WebInvoices::find(102, [1, 2])); // non-active but linked
		$this->assertNull(WebInvoices::find(103, [1, 2]));    // foreign customer
		$this->assertNull(WebInvoices::find(101, []));        // unlinked account
	}

	public function test_invoice_download_is_gated_to_linked_customers(): void
	{
		$user = $this->makeTwoCustomerAccount();
		$this->makeCustomer(3, 'Idegen Cég Kft.', '2026000003');
		$this->makeInvoice(103, 3);

		$this->actingAs($user, 'customer')
			->get(route('invoices.download', ['id' => 103]))
			->assertNotFound();
	}

	public function test_a_non_active_linked_customers_invoice_still_downloads(): void
	{
		// The stale-tab guarantee: customer 1 is active, the invoice belongs to
		// linked customer 2 - the download must still resolve.
		Storage::fake('invoices');
		Storage::disk('invoices')->put('2026/teszt-szamla.pdf', '%PDF-1.4 test');

		$user = $this->makeTwoCustomerAccount();
		$this->makeInvoice(102, 2, '2026/teszt-szamla.pdf');

		$this->actingAs($user, 'customer')
			->get(route('invoices.download', ['id' => 102]))
			->assertOk();
	}

	/** Seed a downloadable invoice row for a customer. */
	private function makeInvoice(int $id, int $customerId, string $pdfPath = '2026/x.pdf'): void
	{
		DB::table('invoices')->insert([
			'id'             => $id,
			'customers_id'   => $customerId,
			'invoice_number' => 'INV-' . $id,
			'invoice_kind'   => 'normal',
			'has_pdf'        => 1,
			'pdf_path'       => $pdfPath,
			'deleted'        => 0,
		]);
	}
}
