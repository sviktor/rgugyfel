<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Location - READ-ONLY view of the shared `prop_locations` table (owned by
 * rgadmin). The portal reads it (through Property) to build a contract's
 * address label; it never writes this table.
 *
 * `name` is the megnevezés (canonical building name); `address` is the postal
 * address (nullable).
 *
 * @property int         $id
 * @property string      $name
 * @property string|null $address
 * @property string|null $complex
 */
class Location extends Model
{
	protected $table   = 'prop_locations';
	public $timestamps = false;

	protected $casts = [
		'status'  => 'integer',
		'deleted' => 'integer',
	];
}
