<?php

namespace Tests\Feature;

use App\Models\CustomerUser;
use Tests\FeatureTestCase;

/**
 * The portal documents page (/dokumentumok) + the gated library download
 * (/dokumentumok/{id}/letoltes). The page lists the PUBLIC portal library
 * (App\Support\WebDocuments - the shared `documents` rows bound to
 * web_documents/portal/documents, uploaded in rgadmin). Both routes are behind
 * auth:customer + customer.verified; the download additionally 404s any id that
 * is not a published portal-library document (the WebDocuments::downloadable gate).
 *
 * The shared `documents` table is owned by rgadmin and is absent from the
 * portal-only test DB, so WebDocuments degrades to an empty library (like
 * SiteContent for web_sections). These tests therefore cover the route + auth +
 * not-found gate contract; the happy-path stream (a real file on the private
 * disk) is out of this harness's scope.
 */
class DocsTest extends FeatureTestCase
{
	public function test_a_guest_is_redirected_from_the_documents_page(): void
	{
		$this->get(route('docs'))->assertRedirect(route('login'));
	}

	public function test_a_guest_is_redirected_from_the_download_route(): void
	{
		$this->get(route('docs.download', ['id' => 1]))->assertRedirect(route('login'));
	}

	public function test_a_verified_customer_sees_the_documents_page(): void
	{
		$user = CustomerUser::factory()->create();

		$this->actingAs($user, 'customer')
			->get(route('docs'))
			->assertOk()
			->assertSee('Dokumentumok');
	}

	public function test_an_unknown_document_id_is_not_found(): void
	{
		$user = CustomerUser::factory()->create();

		$this->actingAs($user, 'customer')
			->get(route('docs.download', ['id' => 999999]))
			->assertNotFound();
	}
}
