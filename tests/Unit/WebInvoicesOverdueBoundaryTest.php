<?php

namespace Tests\Unit;

use App\Models\Invoice;
use App\Support\WebInvoices;
use Illuminate\Support\Carbon;
use ReflectionMethod;
use Tests\TestCase;

/**
 * The portal's overdue boundary must match the admin matrix: an unsettled
 * invoice is overdue the day AFTER its due date, NOT on the due day itself
 * (the matrix uses today > deadline). due_date is a `date` cast (midnight),
 * so the old isPast() flipped a day early. Pure-logic (no DB).
 */
class WebInvoicesOverdueBoundaryTest extends TestCase
{
	protected function tearDown(): void
	{
		Carbon::setTestNow();
		parent::tearDown();
	}

	/** A single-month, unsettled subscription invoice with the given due date. */
	private function dueInvoice(string $due): Invoice
	{
		return (new Invoice)->forceFill([
			'id'           => 9,
			'invoice_kind' => 'normal',
			'payable_type' => 'subscription',
			'payable_id'   => 7,
			'period_start' => '2026-06-01',
			'period_end'   => '2026-06-30',
			'gross'        => '10000.00',
			'due_date'     => $due,
			'has_pdf'      => 0,
		]);
	}

	/** @return array<string, mixed> */
	private function row(Invoice $i): array
	{
		$m = new ReflectionMethod(WebInvoices::class, 'row');
		$m->setAccessible(true);

		// Unsettled + no credits -> the full gross is owed, so the status
		// depends only on the due-date boundary.
		return $m->invoke(null, $i, [], [], false, []);
	}

	public function test_the_due_day_itself_is_still_pending(): void
	{
		Carbon::setTestNow(Carbon::parse('2026-06-15 10:00:00'));

		$this->assertSame('pending', $this->row($this->dueInvoice('2026-06-15'))['status']);
	}

	public function test_the_day_after_the_due_date_is_overdue(): void
	{
		Carbon::setTestNow(Carbon::parse('2026-06-15 10:00:00'));

		$this->assertSame('overdue', $this->row($this->dueInvoice('2026-06-14'))['status']);
	}
}
