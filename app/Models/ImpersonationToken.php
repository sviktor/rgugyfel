<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * ImpersonationToken - the rgugyfel-owned `cus_impersonation_tokens` store.
 *
 * An rgadmin operator MINTS a single-use, ~2 min token (sharing the `mc_rg` DB)
 * and is redirected to this portal's accept route to log in as the linked
 * `cus_users` account ("Belépés az ügyfélkapuba"). The two apps use different
 * APP_KEYs, so a Laravel signed URL cannot be trusted cross-app; this DB token
 * bridges it. Only the sha256 HASH of the raw URL token is stored.
 *
 * @property int         $id
 * @property int         $cus_users_id
 * @property string      $token           sha256 hex of the raw URL token
 * @property int|null    $admin_users_id  the operator who minted it
 * @property \Illuminate\Support\Carbon      $expires_at
 * @property \Illuminate\Support\Carbon|null $used_at
 */
class ImpersonationToken extends Model
{
	protected $table = 'cus_impersonation_tokens';

	public $timestamps = false;

	protected $casts = [
		'expires_at' => 'datetime',
		'used_at'    => 'datetime',
		'created_at' => 'datetime',
	];
}
