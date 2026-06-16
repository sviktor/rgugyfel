<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Product - READ-ONLY view of the shared `products` table (owned by rgadmin,
 * the telecom packages). The portal reads a subscription's attached packages to
 * label the customer's contracts; it never writes this table.
 *
 * `brutto` is the operative monthly price; `type` is 'net' | 'tv' | 'tv net'.
 *
 * @property int         $id
 * @property string      $name
 * @property string|null $brutto
 * @property string|null $type
 * @property int         $deleted
 */
class Product extends Model
{
	protected $table   = 'products';
	public $timestamps = false;

	protected $casts = [
		'brutto'  => 'decimal:2',
		'status'  => 'integer',
		'deleted' => 'integer',
	];
}
