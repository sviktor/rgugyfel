<?php

namespace Tests\Feature;

use Tests\FeatureTestCase;

class LegalTest extends FeatureTestCase
{
	public function test_terms_page_renders_with_a_default_title(): void
	{
		$this->get(route('terms'))
			->assertOk()
			->assertSee('Általános Szerződési Feltételek');
	}

	public function test_privacy_page_renders_with_a_default_title(): void
	{
		$this->get(route('privacy'))
			->assertOk()
			->assertSee('Adatvédelmi Tájékoztató');
	}
}
