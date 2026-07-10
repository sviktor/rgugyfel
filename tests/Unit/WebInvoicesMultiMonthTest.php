<?php

namespace Tests\Unit;

use App\Models\Invoice;
use App\Support\WebInvoices;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Pure-logic tests for the WebInvoices multi-month handling (no DB): an
 * invoice covering period_start..period_end is paid only when EVERY covered
 * month is settled; until then the unsettled months' proportional share of
 * the gross stays owed (the old bug marked a 5-month invoice paid as soon
 * as its FIRST month was settled).
 */
class WebInvoicesMultiMonthTest extends TestCase
{
	/** A Nov 2021 .. Mar 2022 subscription invoice (5 covered months). */
	private function invoice(): Invoice
	{
		return (new Invoice)->forceFill([
			'id'           => 5,
			'invoice_kind' => 'normal',
			'payable_type' => 'subscription',
			'payable_id'   => 7,
			'period_start' => '2021-11-01',
			'period_end'   => '2022-03-31',
			'gross'        => '50000.00',
			'due_date'     => '2021-12-15',
			'has_pdf'      => 0,
		]);
	}

	/**
	 * @param array<string, true> $settled
	 * @param array<string, int>  $credits
	 * @return array<string, mixed>
	 */
	private function row(Invoice $i, array $settled, array $credits = []): array
	{
		$m = new ReflectionMethod(WebInvoices::class, 'row');
		$m->setAccessible(true);

		return $m->invoke(null, $i, $settled, $credits, false, []);
	}

	public function test_all_covered_months_settled_means_paid(): void
	{
		$settled = [];
		foreach (['2021-11', '2021-12', '2022-01', '2022-02', '2022-03'] as $ym) {
			$settled['subscription|7|' . $ym] = true;
		}

		$row = $this->row($this->invoice(), $settled);

		$this->assertSame('paid', $row['status']);
		$this->assertSame(0, $row['outstanding']);
	}

	public function test_a_partially_settled_invoice_is_not_paid(): void
	{
		// Only the FIRST covered month is settled - the old code already
		// reported the whole invoice paid here.
		$row = $this->row($this->invoice(), ['subscription|7|2021-11' => true]);

		$this->assertSame('overdue', $row['status']); // due date long past
		$this->assertSame(40000, $row['outstanding']); // 4/5 of the gross
	}

	public function test_an_unsettled_invoice_owes_the_full_gross(): void
	{
		$row = $this->row($this->invoice(), []);

		$this->assertSame(50000, $row['outstanding']);
	}

	public function test_covered_month_credits_reduce_the_outstanding(): void
	{
		$row = $this->row($this->invoice(), ['subscription|7|2021-11' => true], ['subscription|7|2021-12' => 10000]);

		$this->assertSame(30000, $row['outstanding']); // 40000 share - 10000 credit
	}
}
