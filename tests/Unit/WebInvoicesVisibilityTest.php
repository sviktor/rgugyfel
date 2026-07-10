<?php

namespace Tests\Unit;

use App\Models\Invoice;
use App\Support\WebInvoices;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Pure-logic tests for which invoices the portal shows a customer (no DB): a
 * storno document is hidden (a credit note), and an invoice with no
 * service-month key (no payable link or no period) is hidden because its paid
 * state cannot be derived from the settlement ledger - it would otherwise be a
 * permanent false claim (audit M10). A payable-linked normal invoice shows.
 */
class WebInvoicesVisibilityTest extends TestCase
{
	/** @param array<string, mixed> $attributes */
	private function hidden(array $attributes): bool
	{
		$i = (new Invoice)->forceFill($attributes);
		$m = new ReflectionMethod(WebInvoices::class, 'hiddenFromCustomer');
		$m->setAccessible(true);

		return (bool) $m->invoke(null, $i);
	}

	public function test_a_payable_linked_normal_invoice_is_visible(): void
	{
		$this->assertFalse($this->hidden([
			'invoice_kind' => 'normal',
			'payable_type' => 'subscription',
			'payable_id'   => 7,
			'period_start' => '2026-05-01',
		]));
	}

	public function test_a_customer_only_invoice_without_a_payable_is_hidden(): void
	{
		$this->assertTrue($this->hidden([
			'invoice_kind' => 'normal',
			'payable_type' => null,
			'payable_id'   => null,
			'period_start' => '2026-05-01',
		]));
	}

	public function test_an_invoice_without_a_period_is_hidden(): void
	{
		$this->assertTrue($this->hidden([
			'invoice_kind' => 'normal',
			'payable_type' => 'subscription',
			'payable_id'   => 7,
			'period_start' => null,
		]));
	}

	public function test_a_storno_document_is_hidden(): void
	{
		$this->assertTrue($this->hidden([
			'invoice_kind' => Invoice::KIND_STORNO,
			'payable_type' => 'subscription',
			'payable_id'   => 7,
			'period_start' => '2026-05-01',
		]));
	}
}
