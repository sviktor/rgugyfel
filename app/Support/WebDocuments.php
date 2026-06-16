<?php

namespace App\Support;

use App\Models\Document;

/**
 * Read layer for the portal public document library - the analogue of
 * rgtelekom's App\Support\WebDocuments. Reads the shared `documents` table
 * (owned by rgadmin, uploaded under WEBOLDALAK -> Ügyfélkapu -> Dokumentumok)
 * for the rows bound to the virtual library entity
 * ('web_documents', 'portal', 'documents') and maps each to the display array
 * shape the docs page expects.
 *
 * Files are PRIVATE; the page links to the gated download route
 * (`docs.download`), never to a /storage URL. One query per request.
 */
final class WebDocuments
{
	/** Published library docs (Document rows); null until first use. */
	private static ?array $rows = null;

	/**
	 * Every published library document as a display array
	 * (id / name / file / size / icon / tags / date / url), in display order.
	 *
	 * @example  WebDocuments::all()
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function all(): array
	{
		return array_map([self::class, 'map'], self::rows());
	}

	/**
	 * Display documents filtered to a single tag ('all' = no filter).
	 *
	 * @example  WebDocuments::byTag('ÁSZF')
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function byTag(string $tag): array
	{
		$all = self::all();
		if ($tag === '' || $tag === 'all') {
			return $all;
		}

		return array_values(array_filter($all, static fn (array $d): bool => in_array($tag, $d['tags'], true)));
	}

	/**
	 * Distinct tags across the library (the filter pills), in natural order.
	 *
	 * @example  WebDocuments::tags()  // ['ÁSZF', 'Nyomtatvány', ...]
	 *
	 * @return array<int, string>
	 */
	public static function tags(): array
	{
		$tags = [];
		foreach (self::rows() as $row) {
			foreach ((array) $row->tags as $t) {
				$t = trim((string) $t);
				if ($t !== '') {
					$tags[$t] = true;
				}
			}
		}
		$out = array_keys($tags);
		sort($out, SORT_NATURAL | SORT_FLAG_CASE);

		return $out;
	}

	/**
	 * Resolve a document id to a downloadable library document, or null when the
	 * id is not part of the published portal library (the access gate - private
	 * customer documents are never served here).
	 *
	 * @example  WebDocuments::downloadable(12)   // Document | null
	 */
	public static function downloadable(int $id): ?Document
	{
		// Resilient like rows(): a missing `documents` table yields no match (404),
		// never a 500.
		try {
			return Document::library()->whereKey($id)->first();
		} catch (\Throwable $e) {
			return null;
		}
	}

	/** Published library rows, loaded once per request. */
	private static function rows(): array
	{
		if (self::$rows === null) {
			// Resilient: a missing/unreachable `documents` table (e.g. a fresh
			// rgugyfel-only env or the portal-only test DB before rgadmin migrates
			// it) yields an empty library, not a 500 - mirrors App\Support\SiteContent.
			try {
				self::$rows = Document::library()->get()->all();
			} catch (\Throwable $e) {
				self::$rows = [];
			}
		}

		return self::$rows;
	}

	/** Map a Document to the docs-page display array. */
	private static function map(Document $d): array
	{
		return [
			'id'   => (int) $d->id,
			'name' => (string) ($d->title ?: $d->original_name),
			'file' => (string) $d->original_name,
			'size' => self::formatSize((int) $d->size),
			'icon' => self::icon((string) $d->doc_type),
			'tags' => array_values(array_map('strval', (array) $d->tags)),
			'date' => optional($d->createDate)->format('Y-m-d') ?? '',
			'url'  => route('docs.download', ['id' => $d->id]),
		];
	}

	/** Lucide icon name for a document type. */
	private static function icon(string $type): string
	{
		return match ($type) {
			'image' => 'image',
			'pdf'   => 'file-text',
			default => 'file',
		};
	}

	/** Human-readable file size. */
	private static function formatSize(int $bytes): string
	{
		if ($bytes >= 1048576) {
			return number_format($bytes / 1048576, 1, ',', ' ') . ' MB';
		}
		if ($bytes >= 1024) {
			return number_format($bytes / 1024, 0, ',', ' ') . ' KB';
		}

		return $bytes . ' B';
	}
}
