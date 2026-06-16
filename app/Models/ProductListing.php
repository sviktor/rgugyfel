<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * ProductListing - READ-ONLY view of the shared `product_listings` table (owned
 * by rgadmin, edited under Telekom -> Web csomagok). The portal renders the
 * published marketing cards on the Szerződéseim page (the "Elérhető csomagjaink"
 * list), the SAME source the rgtelekom public /csomagok page uses. It never
 * writes this table.
 *
 * One row per card, grouped by `kind` (akcio | internet | telefon); the rich
 * card fields live in the `data` JSON (plain fields + an `items` features
 * repeater + a `prices` loyalty-term repeater). `status` = published on the web.
 *
 * @property int        $id
 * @property int|null   $products_id
 * @property string     $kind     akcio | internet | telefon
 * @property string     $name
 * @property int        $featured
 * @property int        $pos
 * @property array|null $data
 * @property int        $status   1 = published on the web
 */
class ProductListing extends Model
{
	protected $table   = 'product_listings';
	public $timestamps = false;

	protected $casts = [
		'products_id' => 'integer',
		'featured'    => 'integer',
		'pos'         => 'integer',
		'data'        => 'array',
		'status'      => 'integer',
	];

	/**
	 * Scope: published, non-deleted cards (what the public/portal shows).
	 *
	 * @example  ProductListing::published()->orderBy('pos')->get()
	 */
	public function scopePublished(Builder $q): Builder
	{
		return $q->where('status', 1)->where('deleted', 0);
	}
}
