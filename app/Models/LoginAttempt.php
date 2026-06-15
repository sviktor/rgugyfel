<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * LoginAttempt - one row per FAILED portal login (cus_login_attempts). Counted
 * inside a rolling window by the brute-force lockout logic in AuthController.
 *
 * @property int         $id
 * @property string      $email
 * @property string|null $ip
 */
class LoginAttempt extends Model
{
	protected $table = 'cus_login_attempts';

	public $timestamps = false;

	protected $fillable = ['email', 'ip', 'created_at'];

	protected $casts = ['created_at' => 'datetime'];
}
