<?php

namespace Tests\Feature;

use App\Http\Middleware\SiteAvailability;
use App\Support\SiteContent;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

/**
 * The portal on/off gate (App\Http\Middleware\SiteAvailability), driven by the
 * CMS "Oldal elérhetőség" switch (web_sections: portal / global.availability,
 * edited in rgadmin under WEBOLDALAK -> Ügyfélkapu -> Beállítások).
 *
 * The web_sections table is OWNED by rgadmin and absent from the portal-only
 * test DB, so these tests fake SiteContent instead of seeding a row: four tests
 * drive the handle() branches directly (online / off / preview key / wrong key)
 * and one proves the gate is actually wired into the web group (a real request
 * 503s). No DB is touched, so it extends the plain TestCase (no RefreshDatabase).
 */
class SiteAvailabilityTest extends TestCase
{
	protected function setUp(): void
	{
		parent::setUp();

		// The maintenance + auth views @vite; the test DB has no build manifest.
		$this->withoutVite();
	}

	public function test_it_passes_through_when_the_portal_is_online(): void
	{
		$mw     = new SiteAvailability($this->fakeCms('1'));
		$result = $mw->handle($this->requestWithSession('/'), fn () => new Response('OK'));

		$this->assertSame(200, $result->getStatusCode());
		$this->assertSame('OK', $result->getContent());
	}

	public function test_a_visitor_gets_the_maintenance_page_when_the_portal_is_off(): void
	{
		$mw = new SiteAvailability($this->fakeCms('0', 'secret-key'));

		// next() must NOT be reached - the maintenance view is returned instead.
		$result = $mw->handle($this->requestWithSession('/'), fn () => new Response('OK'));

		$this->assertSame(503, $result->getStatusCode());
		$this->assertStringContainsString('Karbantartás', $result->getContent());
	}

	public function test_the_preview_key_unlocks_browsing_for_the_session(): void
	{
		$mw      = new SiteAvailability($this->fakeCms('0', 'secret-key'));
		$request = $this->requestWithSession('/?elonezet=secret-key');

		$result = $mw->handle($request, fn () => new Response('OK'));

		// next() ran (the real page renders, not the maintenance view)...
		$this->assertSame('OK', $result->getContent());
		// ...the session is flagged so later requests stay unlocked...
		$this->assertTrue($request->session()->get('site_preview'));
		// ...and the layout gets the red-banner flag.
		$this->assertTrue(View::shared('siteOffline'));
	}

	public function test_a_wrong_preview_key_still_shows_maintenance(): void
	{
		$mw     = new SiteAvailability($this->fakeCms('0', 'secret-key'));
		$result = $mw->handle($this->requestWithSession('/?elonezet=wrong'), fn () => new Response('OK'));

		$this->assertSame(503, $result->getStatusCode());
	}

	public function test_the_gate_is_wired_into_the_web_group(): void
	{
		// Override the SiteContent singleton so the HTTP-resolved middleware sees
		// the portal as OFF, then hit a real route: it must 503, proving the
		// middleware actually runs on web requests (not just in isolation).
		$this->app->instance(SiteContent::class, $this->fakeCms('0', ''));

		$this->get('/login')
			->assertStatus(503)
			->assertSee('Karbantartás');
	}

	/** A SiteContent double whose get() returns the given availability values. */
	private function fakeCms(string $online, string $token = ''): SiteContent
	{
		return new class($online, $token) extends SiteContent {
			public function __construct(private string $online, private string $token)
			{
			}

			public function get(string $key): string
			{
				return match ($key) {
					'global.availability.online'        => $this->online,
					'global.availability.preview_token' => $this->token,
					default                             => '',
				};
			}
		};
	}

	/** A GET request with a real (array-driver) session attached. */
	private function requestWithSession(string $uri): Request
	{
		$request = Request::create($uri, 'GET');
		$request->setLaravelSession($this->app['session']->driver());

		return $request;
	}
}
