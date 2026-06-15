<?php

namespace App\Http\Controllers;

use App\Support\SiteContent;
use Illuminate\View\View;

/**
 * LegalController - the portal's public legal pages (ÁSZF / Adatvédelem),
 * linked from the registration form. CMS-driven from the portal's own
 * `web_sections` (the `legal.terms` / `legal.privacy` sections), mirroring
 * rgsite/rgtelekom. The rgadmin "Ügyfélkapu -> Jogi oldalak" editor that fills
 * these in is a follow-up; until then the page renders a placeholder (so the
 * link is real, never a 404).
 */
class LegalController extends Controller
{
	/**
	 * Adatvédelmi Tájékoztató.
	 *
	 * @example  GET /adatvedelem  ->  LegalController::privacy()
	 */
	public function privacy(SiteContent $cms): View
	{
		return $this->page($cms, 'legal.privacy', 'Adatvédelmi Tájékoztató');
	}

	/**
	 * Általános Szerződési Feltételek.
	 *
	 * @example  GET /aszf  ->  LegalController::terms()
	 */
	public function terms(SiteContent $cms): View
	{
		return $this->page($cms, 'legal.terms', 'Általános Szerződési Feltételek');
	}

	/**
	 * Render a CMS legal section (title + rich body), falling back to a default
	 * title + placeholder body when the section has no content yet.
	 */
	private function page(SiteContent $cms, string $key, string $defaultTitle): View
	{
		$title    = $cms->get($key . '.title');
		$bodyHtml = $cms->get($key . '.body') !== '' ? $cms->rich($key . '.body') : null;

		return view('pages.legal', [
			'title' => $title !== '' ? $title : $defaultTitle,
			'body'  => $bodyHtml,
		]);
	}
}
