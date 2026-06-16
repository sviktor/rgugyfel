<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Property - READ-ONLY view of the shared `prop_properties` table (owned by
 * rgadmin). The portal reads the unit a subscription serves to build the
 * contract's address label; it never writes this table.
 *
 * @property int         $id
 * @property int|null    $prop_locations_id
 * @property string|null $name
 * @property string|null $building
 * @property string|null $floor
 * @property string|null $door
 * @property string|null $apartment_no
 */
class Property extends Model
{
	protected $table   = 'prop_properties';
	public $timestamps = false;

	protected $casts = [
		'prop_locations_id' => 'integer',
		'status'            => 'integer',
		'deleted'           => 'integer',
	];

	/**
	 * The location (building) this unit belongs to.
	 *
	 * @example  $property->location?->address
	 */
	public function location(): BelongsTo
	{
		return $this->belongsTo(Location::class, 'prop_locations_id');
	}
}
