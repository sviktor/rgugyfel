<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Read-only accessor for the shared global `settings` table (owned + written by
 * rgadmin; the portal only READS it). Currently used to gate the bank-transfer
 * details behind the operator's content-verification flag
 * ('content_verify.portal.global.bank' - set in rgadmin under WEBOLDALAK ->
 * Ügyfélkapu -> Beállítások -> "Ellenőrizve"): the account details stay hidden
 * on the portal until an operator confirms they are real (not seed/test) data.
 *
 * @property int         $id
 * @property string      $name
 * @property string|null $value
 */
class Setting extends Model
{
	protected $table   = 'settings';
	public $timestamps = false;

	/**
	 * True when a setting row with this exact name exists. Resilient: a missing
	 * `settings` table (a fresh env before rgadmin migrates it) yields false.
	 * NOT named has() - that collides with Eloquent's relationship-existence
	 * Model::has(), which larastan then reads as a (missing) relation query.
	 *
	 * @example  Setting::present('content_verify.portal.global.bank')
	 */
	public static function present(string $name): bool
	{
		try {
			return self::where('name', $name)->exists();
		} catch (\Throwable) {
			return false;
		}
	}
}
