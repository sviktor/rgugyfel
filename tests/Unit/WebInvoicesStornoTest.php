<?php

namespace Tests\Unit;

use App\Models\Invoice;
use App\Support\WebInvoices;
use Illuminate\Support\Collection;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Pure-logic tests for the WebInvoices storno handling (no DB): the
 * cancelled-id resolution from the loaded storno rows and the 'cancelled'
 * row status - a storno-cancelled original is never a claim, while an
 * unrelated invoice keeps its ledger-derived status.
 */
class WebInvoicesStornoTest extends TestCase
{
	/** @param array<string, mixed> $attributes */
	private function invoice(array $attributes): Invoice
	{
		return (new Invoice)->forceFill($attributes);
	}

	/** @param array<int, true> $cancelled @return array<string, mixed> */
	private function row(Invoice $i, array $cancelled): array
	{
		$m = new ReflectionMethod(WebInvoices::class, 'row');
		$m->setAccessible(true);

		return $m->invoke(null, $i, [], [], false, $cancelled);
	}

	public function test_cancelled_ids_come_from_the_loaded_storno_rows(): void
	{
		$rows = new Collection([
			$this->invoice(['id' => 10, 'invoice_kind' => 'normal', 'gross' => '10295.00']),
			$this->invoice(['id' => 11, 'invoice_kind' => Invoice::KIND_STORNO, 'corrects_invoice_id' => 10, 'gross' => '-10295.00']),
			$this->invoice(['id' => 12, 'invoice_kind' => Invoice::KIND_STORNO, 'corrects_invoice_id' => null, 'gross' => '-5000.00']),
		]);

		$this->assertSame([10 => true], WebInvoices::cancelledIds($rows));
	}

	public function test_a_cancelled_original_renders_as_cancelled_with_zero_outstanding(): void
	{
		$i = $this->invoice([
			'id'             => 10,
			'invoice_number' => '1/2026/42',
			'invoice_kind'   => 'normal',
			'gross'          => '10295.00',
			'due_date'       => '2020-01-15', // long past - would otherwise be overdue
			'has_pdf'        => 0,
		]);

		$row = $this->row($i, [10 => true]);

		$this->assertSame('cancelled', $row['status']);
		$this->assertSame(0, $row['outstanding']);
		$this->assertSame(10295, $row['amount']); // the billed amount stays visible
	}

	public function test_an_uncancelled_invoice_keeps_the_ledger_derived_status(): void
	{
		$i = $this->invoice([
			'id'           => 20,
			'invoice_kind' => 'normal',
			'gross'        => '10295.00',
			'due_date'     => '2020-01-15',
			'has_pdf'      => 0,
		]);

		$row = $this->row($i, [10 => true]); // a DIFFERENT id is cancelled

		$this->assertSame('overdue', $row['status']);
		$this->assertSame(10295, $row['outstanding']);
	}
}
