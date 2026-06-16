<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Read-only view of the shared `documents` table (owned by rgadmin - uploads
 * happen there). This portal only renders + serves its PUBLIC document library
 * (the Ügyfélkapu Dokumentumok page), bound to the virtual entity
 * ('web_documents', 'portal', 'documents').
 *
 * Files live on the PRIVATE `documents` disk (no public URL); the portal page
 * streams them through the gated download route, never directly.
 *
 * @property int         $id
 * @property string      $table_data
 * @property string      $cell_name
 * @property string      $cell_data
 * @property string|null $title
 * @property string      $filename
 * @property string      $original_name
 * @property string      $doc_type
 * @property string|null $mime
 * @property int         $size
 * @property array|null  $tags
 * @property int         $pos
 * @property int         $status
 * @property string      $createDate
 */
class Document extends Model
{
	protected $table   = 'documents';
	public $timestamps = false;

	/** The private filesystem disk documents live on. */
	public const DISK = 'documents';

	/** Virtual entity binding of the portal public document library. */
	public const LIBRARY = ['web_documents', 'portal', 'documents'];

	protected $casts = [
		'tags'       => 'array',
		'size'       => 'integer',
		'pos'        => 'integer',
		'status'     => 'integer',
		'createDate' => 'datetime',
	];

	/**
	 * Scope: the published (active) portal public document library, ordered for
	 * display.
	 *
	 * @example  Document::library()->get()
	 */
	public function scopeLibrary(Builder $q): Builder
	{
		[$table, $cell, $key] = self::LIBRARY;

		return $q->where('table_data', $table)
			->where('cell_name', $cell)
			->where('cell_data', $key)
			->where('status', 1)
			->orderBy('pos')
			->orderBy('id');
	}

	/**
	 * The path relative to the `documents` disk root.
	 *
	 * @example  $doc->diskPath()  // 'uploads/documents/2026/06/ab12-….pdf'
	 */
	public function diskPath(): string
	{
		$ts  = $this->createDate?->getTimestamp() ?? time();
		$dir = 'uploads/documents/' . date('Y/m', $ts);

		return $dir . '/' . $this->filename;
	}
}
