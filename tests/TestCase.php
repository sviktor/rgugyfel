<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

/**
 * Base test case for the rgugyfel suite.
 *
 * Carries the safety guard that keeps the shared production-ish `mc_rg`
 * database untouchable from tests: the suite runs ONLY against a database whose
 * name ends in `_test` (mc_rg_test). Mirrors the rgadmin guard.
 */
abstract class TestCase extends BaseTestCase
{
	/**
	 * Validate the database target right after the app boots and before any
	 * test-trait setup (RefreshDatabase -> migrate:fresh) runs, for every test.
	 *
	 * @example  (automatic - every test boots through this)
	 */
	protected function refreshApplication(): void
	{
		parent::refreshApplication();
		$this->assertSafeTestDatabase();
	}

	/**
	 * Abort unless APP_ENV is testing AND the default connection's database name
	 * ends in `_test`. A misconfigured phpunit.xml (or a stray DB_DATABASE in the
	 * shell) then fails loudly instead of wiping the shared `mc_rg`.
	 */
	private function assertSafeTestDatabase(): void
	{
		$config     = $this->app['config'];
		$connection = (string) $config->get('database.default');
		$database   = (string) $config->get("database.connections.{$connection}.database");

		if ($config->get('app.env') !== 'testing' || ! str_ends_with($database, '_test')) {
			throw new RuntimeException(
				"UNSAFE TEST DATABASE: env='" . (string) $config->get('app.env') . "', "
				. "connection='{$connection}', database='{$database}'. "
				. 'Tests only run against APP_ENV=testing and a database whose name ends in _test '
				. '(expected mc_rg_test - check phpunit.xml).',
			);
		}
	}
}
