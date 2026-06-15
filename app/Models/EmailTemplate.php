<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * EmailTemplate - read model for the shared `email_templates` table (owned by
 * rgadmin). The portal reads the admin-editable verify/reset templates and
 * substitutes their {curly} variables. If a row is missing or empty (e.g. the
 * rgadmin seeding has not run yet), a built-in Hungarian fallback is used so the
 * portal always sends a usable mail.
 *
 * Single-language (hu) port of rgadmin's EmailTemplate::useTemplate().
 *
 * @property string $alias
 * @property string $subject       JSON {"hu":"…"}
 * @property string $templates     JSON {"hu":"<html>"}
 */
class EmailTemplate extends Model
{
	protected $table = 'email_templates';

	public $timestamps = false;

	/**
	 * Build a ready-to-send subject + body from a template alias, substituting
	 * the given {curly} variables (falls back to built-in content if needed).
	 *
	 * @example
	 *   $mail = EmailTemplate::useTemplate('customer_verify_email', [
	 *       '{neve}' => 'Kis Éva', '{verify_url}' => $url, '{cim}' => 'Royal Telekom Ügyfélkapu',
	 *   ]);
	 *   // ['subject' => '…', 'body' => '<html>…</html>']
	 *
	 * @param  array<string, string>  $data
	 * @return array{subject: string, body: string}
	 */
	public static function useTemplate(string $alias, array $data = [], string $language = 'hu'): array
	{
		// Resilient read: a missing/unreachable email_templates table (e.g. a
		// fresh test DB) falls through to the built-in fallback below.
		try {
			$row = static::query()->where('alias', $alias)->where('deleted', 0)->first();
		} catch (\Throwable $e) {
			$row = null;
		}

		$subject = '';
		$body    = '';

		if ($row) {
			$subjects = json_decode((string) $row->subject, true) ?: [];
			$bodies   = json_decode((string) $row->templates, true) ?: [];
			$subject  = (string) ($subjects[$language] ?? '');
			$body     = (string) ($bodies[$language] ?? '');
		}

		// Built-in fallback when the admin row is missing or left empty.
		$fallback = static::fallback($alias);
		if ($subject === '') {
			$subject = $fallback['subject'];
		}
		if ($body === '') {
			$body = $fallback['body'];
		}

		foreach ($data as $key => $value) {
			$subject = str_replace($key, (string) $value, $subject);
			$body    = str_replace($key, (string) $value, $body);
		}

		return ['subject' => $subject, 'body' => $body];
	}

	/**
	 * Built-in Hungarian default content per alias (mirrors the rgadmin
	 * templates() `defaults` block).
	 *
	 * @return array{subject: string, body: string}
	 */
	private static function fallback(string $alias): array
	{
		return match ($alias) {
			'customer_verify_email' => [
				'subject' => '{cim} - e-mail cím megerősítése',
				'body'    => '<p>Kedves {neve}!</p>'
					. '<p>Köszönjük, hogy regisztrált a(z) {cim} felületére. A fiók aktiválásához kérjük, '
					. 'erősítse meg e-mail címét az alábbi gombra kattintva:</p>'
					. '<p><a href="{verify_url}">E-mail cím megerősítése</a></p>'
					. '<p>Ha a gomb nem működik, másolja be ezt a címet a böngészőjébe:<br>{verify_url}</p>'
					. '<p>Ha nem Ön regisztrált, kérjük, hagyja figyelmen kívül ezt a levelet.</p>'
					. '<p>Üdvözlettel,<br>{cim}</p>',
			],
			'customer_password_reset' => [
				'subject' => '{cim} - jelszó helyreállítása',
				'body'    => '<p>Kedves {neve}!</p>'
					. '<p>Jelszó-helyreállítási kérelmet kaptunk a(z) {cim} fiókjához. Új jelszó beállításához '
					. 'kattintson az alábbi linkre:</p>'
					. '<p><a href="{reset_url}">Új jelszó beállítása</a></p>'
					. '<p>Ha a gomb nem működik, másolja be ezt a címet a böngészőjébe:<br>{reset_url}</p>'
					. '<p>A link {ervenyesseg} percig érvényes. Ha nem Ön kérte a helyreállítást, kérjük, '
					. 'hagyja figyelmen kívül ezt a levelet - jelszava változatlan marad.</p>'
					. '<p>Üdvözlettel,<br>{cim}</p>',
			],
			default => ['subject' => '', 'body' => ''],
		};
	}
}
