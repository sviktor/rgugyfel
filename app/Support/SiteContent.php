<?php

namespace App\Support;

use App\Models\WebSection;
use Illuminate\Support\HtmlString;

/**
 * Blade-facing accessor for the CMS-managed content of THIS site (the
 * `web_sections` rows with site='portal', edited in rgadmin under
 * WEBOLDALAK). Shared into every view as $cms (AppServiceProvider::boot).
 *
 * Lazy: the FIRST accessor call loads ALL of the site's rows in ONE query
 * (a few dozen tiny rows), keyed "page.section" - the layout partials need
 * the globals on every page anyway. No caching (one indexed select per
 * request); copy-adapted from rgsite (only the SITE constant differs).
 *
 * Field keys are "page.section.field", section keys "page.section":
 *
 * @example  {{ $cms->get('global.analytics.ga_id') }}         // plain text field
 * @example  {!! $cms->text('home.hero.lede') !!}              // textarea: nl2br over escaped
 * @example  {!! $cms->rich('about.story.body') !!}            // CKEditor HTML, raw (admins trusted)
 * @example  @foreach ($cms->items('global.faq') as $f) ...    // repeater rows
 * @example  @if ($cms->has('home.testimonials')) ... @endif   // section exists + visible
 */
class SiteContent
{
	/** The web_sections.site value of this app. */
	private const SITE = 'portal';

	/** "page.section" => ['data' => array, 'status' => int]; null until first use. */
	private ?array $sections = null;

	/**
	 * A plain field value ('' when the row/field is missing).
	 *
	 * @example  $cms->get('global.analytics.ga_id')
	 */
	public function get(string $key): string
	{
		[$sectionKey, $field] = $this->splitKey($key);
		$value = $this->load()[$sectionKey]['data'][$field] ?? '';

		return is_scalar($value) ? (string) $value : '';
	}

	/**
	 * A multiline (textarea) field: escaped, newlines become <br>. Echo with
	 * {!! !!} (or pass to a partial's {{ }} - HtmlString passes through).
	 *
	 * @example  {!! $cms->text('home.hero.lede') !!}
	 */
	public function text(string $key): HtmlString
	{
		return new HtmlString(nl2br(e($this->get($key))));
	}

	/**
	 * A rich (CKEditor) field: raw HTML - admin operators are trusted.
	 *
	 * @example  {!! $cms->rich('about.story.body') !!}
	 */
	public function rich(string $key): HtmlString
	{
		return new HtmlString($this->get($key));
	}

	/**
	 * The repeater rows of a section ([] when missing or hidden). Rows the
	 * operator unchecked in the editor (_active = '0') are skipped; rows
	 * saved before that feature (no _active key) count as visible.
	 *
	 * @example  foreach ($cms->items('global.analytics') as $f) { $f['title']; }
	 */
	public function items(string $key): array
	{
		$section = $this->load()[$key] ?? null;
		if (! $section || $section['status'] !== 1) {
			return [];
		}

		$items = $section['data']['items'] ?? [];
		if (! is_array($items)) {
			return [];
		}

		return array_values(array_filter($items, fn ($item) => ($item['_active'] ?? '1') !== '0'));
	}

	/**
	 * Does the section exist, is it visible (status=1), and non-empty?
	 * Wrap every optional section in this so a deactivated section
	 * disappears cleanly.
	 *
	 * @example  @if ($cms->has('home.testimonials')) ... @endif
	 */
	public function has(string $key): bool
	{
		$section = $this->load()[$key] ?? null;

		return $section !== null
			&& $section['status'] === 1
			&& ! empty($section['data']);
	}

	/** Load all rows of this site once, keyed "page.section". */
	private function load(): array
	{
		if ($this->sections === null) {
			$this->sections = [];
			// Resilient: a missing/unreachable web_sections table (e.g. a fresh
			// test DB before rgadmin migrates it) yields empty CMS, not a 500.
			try {
				foreach (WebSection::forSite(self::SITE)->get() as $row) {
					$this->sections[$row->page . '.' . $row->section] = [
						'data'   => $row->data ?? [],
						'status' => (int) $row->status,
					];
				}
			} catch (\Throwable $e) {
				// leave $this->sections empty
			}
		}

		return $this->sections;
	}

	/** "page.section.field" -> ["page.section", "field"]. */
	private function splitKey(string $key): array
	{
		$parts = explode('.', $key, 3);

		return [($parts[0] ?? '') . '.' . ($parts[1] ?? ''), $parts[2] ?? ''];
	}
}
