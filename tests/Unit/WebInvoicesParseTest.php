<?php

namespace Tests\Unit;

use App\Support\WebInvoices;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Pure-logic tests for the WebInvoices invoice-document parsing (no DB): the
 * line items read from the flattened `xml_data` (a single `<tetel>` is an assoc
 * array, many are a 0-indexed list - the distinction that broke once), the item
 * name cleaning (runs of 2+ spaces -> line breaks) and the money parsing.
 *
 * The parsers are private static; reflection keeps them testable without
 * threading a DB-bound Invoice through.
 */
class WebInvoicesParseTest extends TestCase
{
	/** @param array<string, mixed> $xml @return array<int, array<string, mixed>> */
	private function lines(array $xml): array
	{
		$m = new ReflectionMethod(WebInvoices::class, 'lines');
		$m->setAccessible(true);

		return $m->invoke(null, $xml);
	}

	private function invokeStatic(string $method, mixed $arg): mixed
	{
		$m = new ReflectionMethod(WebInvoices::class, $method);
		$m->setAccessible(true);

		return $m->invoke(null, $arg);
	}

	public function test_a_single_tetel_object_yields_one_line_with_real_values(): void
	{
		$xml = ['tetelek' => ['tetel' => [
			'termeknev'     => 'szerződésszám: SV15-00079    [17/2015] SZJ 64.20.18.0  Silver díjcsomag(1) időszak: 2026.06.01-2026.06.30',
			'mennyegys'     => 'hó',
			'menny'         => '1.00',
			'nettoegysegar' => '8233.00',
			'nettoar'       => '8233.00',
			'afakulcs'      => '5',
			'afaertek'      => '411.65',
			'bruttoar'      => '8644.65',
		]]];

		$lines = $this->lines($xml);

		$this->assertCount(1, $lines);
		$this->assertSame('1 hó', $lines[0]['qty']);
		$this->assertSame(8233, $lines[0]['net']);
		$this->assertSame(412, $lines[0]['vat']);   // 411.65 rounded
		$this->assertSame(8645, $lines[0]['gross']); // 8644.65 rounded
		// The full original termeknev is kept, with 2+ spaces turned into line breaks.
		$this->assertStringContainsString('szerződésszám: SV15-00079', $lines[0]['name']);
		$this->assertStringContainsString('Silver díjcsomag(1)', $lines[0]['name']);
		$this->assertStringContainsString("\n", $lines[0]['name']);
		$this->assertStringNotContainsString('  ', $lines[0]['name']); // no leftover double spaces
	}

	public function test_multiple_tetel_list_yields_a_line_per_item(): void
	{
		$xml = ['tetelek' => ['tetel' => [
			['termeknev' => 'Gold díjcsomag', 'menny' => '1.00', 'mennyegys' => 'hó', 'nettoar' => '7012.00', 'afaertek' => '350.60', 'bruttoar' => '7362.60'],
			['termeknev' => 'HBO Max Pak', 'menny' => '1.00', 'mennyegys' => 'hó', 'nettoar' => '2204.80', 'afaertek' => '595.30', 'bruttoar' => '2800.10'],
		]]];

		$lines = $this->lines($xml);

		$this->assertCount(2, $lines);
		$this->assertSame('Gold díjcsomag', $lines[0]['name']);
		$this->assertSame(7363, $lines[0]['gross']);
		$this->assertSame('HBO Max Pak', $lines[1]['name']);
		$this->assertSame(2800, $lines[1]['gross']);
	}

	public function test_missing_or_empty_tetelek_yields_no_lines(): void
	{
		$this->assertSame([], $this->lines([]));
		$this->assertSame([], $this->lines(['tetelek' => []]));
		$this->assertSame([], $this->lines(['tetelek' => ['tetel' => 'not-an-array']]));
	}

	public function test_money_int_parses_hungarian_and_plain_amounts(): void
	{
		$this->assertSame(6652, $this->invokeStatic('moneyInt', '6 651,75'));
		$this->assertSame(8233, $this->invokeStatic('moneyInt', '8233.00'));
		$this->assertSame(10295, $this->invokeStatic('moneyInt', '10295'));
		$this->assertSame(0, $this->invokeStatic('moneyInt', ''));
		$this->assertSame(0, $this->invokeStatic('moneyInt', 'ingyenes'));
	}

	public function test_clean_name_breaks_runs_of_spaces_into_lines(): void
	{
		$this->assertSame("a\nb\nc d", $this->invokeStatic('cleanName', 'a    b  c d'));
		$this->assertSame('plain', $this->invokeStatic('cleanName', '  plain  '));
	}
}
