<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Read-only view of the shared `web_sections` CMS table (owned by rgadmin -
 * the editor lives there; this site only renders the content through the
 * App\Support\SiteContent helper shared into Blade as $cms).
 *
 * One row per (site, page, section); every field value lives in the `data`
 * JSON (plain fields + an optional `items` repeater + image URLs).
 *
 * @property int        $id
 * @property string     $site     royalgroup | telekom | portal
 * @property string     $page     global | home | ...
 * @property string     $section  analytics | hero | ...
 * @property array|null $data
 * @property int        $status   1 = section visible on the site
 */
class WebSection extends Model
{
	protected $table = 'web_sections';

	public $timestamps = false;

	protected $casts = [
		'data'   => 'array',
		'status' => 'integer',
	];

	/**
	 * Scope: every section row of one site.
	 *
	 * @example  WebSection::forSite('portal')->get()
	 */
	public function scopeForSite(Builder $q, string $site): Builder
	{
		return $q->where('site', $site);
	}
}
