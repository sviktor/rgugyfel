<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Base for portal feature tests. Runs against the dedicated MariaDB `mc_rg_test`
 * (forced + guarded - see phpunit.xml + TestCase) so RefreshDatabase can never
 * touch the shared mc_rg. `withoutVite()` stubs the asset helper (the test DB
 * has no build manifest); a missing `web_sections` table degrades to empty CMS
 * via App\Support\SiteContent, so the layouts still render.
 */
abstract class FeatureTestCase extends TestCase
{
	use RefreshDatabase;

	protected function setUp(): void
	{
		parent::setUp();

		$this->withoutVite();
	}
}
