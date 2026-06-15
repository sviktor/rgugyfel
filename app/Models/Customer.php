<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Customer - read model for the shared CRM `customers` table (owned by rgadmin).
 *
 * The portal does NOT manage customers; it reads a linked customer's slim
 * columns + the `customer_data` JSON (birth date, phones, etc.) once a
 * cus_users account is bound to one. Birth date lives at
 * `customer_data['birth_date']`; primary e-mail in the `email` column.
 *
 * @property int         $id
 * @property string|null $customer_number
 * @property string      $name
 * @property string|null $email
 * @property array|null  $customer_data
 * @property int         $status
 */
class Customer extends Model
{
	protected $table = 'customers';

	public $timestamps = false;

	protected $casts = [
		'customer_data' => 'array',
		'status'        => 'integer',
	];
}
