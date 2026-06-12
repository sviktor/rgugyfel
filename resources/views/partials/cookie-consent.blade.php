{{--
	GDPR cookie consent - first-visit bottom banner + settings modal.
	Ported from the Claude Design handoff (rg-cookies.jsx / Royal Group.html).
	Logic: resources/js/cookie-consent.js (Alpine 'cookieConsent' component),
	styles: resources/css/cookie-consent.css. The decision is stored in a plain
	cookie (App\Support\CookieConsent::COOKIE) for COOKIE_CONSENT_DAYS days; the
	layouts gate the GA snippet server-side via CookieConsent::allows().
	Reopen the modal from anywhere with a `data-cookie-settings` attribute
	(auth side copyright line + portal sidebar "Sütibeállítások" link).
--}}
@php
	$consentDays = (int) config('consent.days', 180);
	$consentHost = request()->getHost();
	// Defensive: the partial must work even before the $cms read layer is wired.
	$consentGaId = isset($cms) ? $cms->get('global.analytics.ga_id') : '';
	$consentCategories = [
		[
			'id' => 'necessary',
			'locked' => true,
			'title' => 'Feltétlenül szükséges',
			'desc' => 'A weboldal alapvető működéséhez elengedhetetlen sütik (bejelentkezés, munkamenet, biztonság, a sütibeállítások tárolása). Ezek hozzájárulás nélkül is használhatók - GDPR 6. cikk (1) f).',
			'cookies' => [
				[\App\Support\CookieConsent::COOKIE, $consentHost, 'Az Ön sütibeállításainak tárolása', $consentDays . ' nap'],
				[config('session.cookie'), $consentHost, 'Munkamenet-azonosító a biztonságos bejelentkezéshez', 'Munkamenet'],
				['XSRF-TOKEN', $consentHost, 'Űrlapküldések biztonsági ellenőrzése (CSRF védelem)', 'Munkamenet'],
			],
		],
		[
			'id' => 'preferences',
			'locked' => false,
			'title' => 'Kényelmi beállítások',
			'desc' => 'Megjegyzik az Ön választásait (pl. felületi beállítások), hogy a következő látogatáskor személyre szabottabb élményt nyújthassunk.',
			'cookies' => [],
		],
		[
			'id' => 'statistics',
			'locked' => false,
			'title' => 'Statisztikai',
			'desc' => 'Anonimizált adatokat gyűjtenek arról, hogyan használják látogatóink az oldalt. Segítenek megérteni, mely tartalmak hasznosak, és hol javíthatunk.',
			'cookies' => $consentGaId !== '' ? [
				['_ga, _ga_*', 'Google Analytics', 'Látogatottsági statisztika, anonim mérés', '24 hónap'],
			] : [],
		],
		[
			'id' => 'marketing',
			'locked' => false,
			'title' => 'Marketing',
			'desc' => 'Harmadik felek által elhelyezett sütik, amelyek érdeklődési körén alapuló hirdetések megjelenítését teszik lehetővé más weboldalakon.',
			'cookies' => [],
		],
	];
@endphp
<div
	x-data="cookieConsent({
		cookieName: @js(\App\Support\CookieConsent::COOKIE),
		days: {{ $consentDays }},
		gaId: @js($consentGaId),
	})"
	@keydown.escape.window="closeSettings()"
>
	{{-- First-visit consent banner --}}
	<div x-show="bannerVisible" x-cloak class="rg-cookie-banner" role="region" aria-label="Sütikre vonatkozó hozzájárulás">
		<div class="rg-cookie-banner-inner">
			<div class="rg-cookie-banner-text">
				<h2>Az Ön adatai - az Ön döntése</h2>
				<p>
					Weboldalunk sütiket használ. A feltétlenül szükséges sütik a működéshez kellenek; statisztikai
					és marketing sütiket <strong>kizárólag az Ön hozzájárulásával</strong> helyezünk el. Döntését
					bármikor módosíthatja a „Sütibeállítások” hivatkozással.
				</p>
			</div>
			<div class="rg-cookie-banner-actions">
				<button type="button" class="rt-btn rt-btn-primary" @click="acceptAll()">Összes elfogadása</button>
				<button type="button" class="rt-btn rt-btn-secondary" @click="rejectAll()">Elutasítás</button>
				<button type="button" class="rg-cookie-link-btn" @click="openSettings()">Beállítások testreszabása</button>
			</div>
		</div>
	</div>

	{{-- Settings modal (closable only once a decision exists) --}}
	<div x-show="settingsOpen" x-cloak class="rg-cookie-modal-backdrop" @click.self="closeSettings()">
		<div class="rg-cookie-modal" role="dialog" aria-modal="true" aria-labelledby="rg-cookie-modal-title">
			<div class="rg-cookie-modal-head">
				<div>
					<span class="rg-cookie-eyebrow">Adatvédelem</span>
					<h2 id="rg-cookie-modal-title">Sütibeállítások</h2>
				</div>
				<button type="button" x-show="saved" class="rg-cookie-modal-close" aria-label="Bezárás" @click="closeSettings()">
					<i data-lucide="x" class="lucide-md"></i>
				</button>
			</div>

			<div class="rg-cookie-modal-body">
				<p class="rg-cookie-modal-lede">
					Itt kategóriánként eldöntheti, mely sütik használatához járul hozzá. Hozzájárulását bármikor
					módosíthatja vagy visszavonhatja a „Sütibeállítások” hivatkozással.
				</p>
				@foreach ($consentCategories as $cat)
					<div class="rg-cookie-cat" x-data="{ open: false }">
						<div class="rg-cookie-cat-head">
							<div class="rg-cookie-cat-titles">
								<h3 id="rg-cat-{{ $cat['id'] }}">{{ $cat['title'] }}</h3>
								<p>{{ $cat['desc'] }}</p>
							</div>
							@if ($cat['locked'])
								<button type="button" role="switch" aria-checked="true" aria-labelledby="rg-cat-{{ $cat['id'] }}" disabled class="rg-toggle is-on is-locked">
									<span class="rg-toggle-knob"></span>
									<span class="rg-toggle-text">Mindig aktív</span>
								</button>
							@else
								<button type="button" role="switch" aria-labelledby="rg-cat-{{ $cat['id'] }}" class="rg-toggle"
									:aria-checked="choices.{{ $cat['id'] }}"
									:class="{ 'is-on': choices.{{ $cat['id'] }} }"
									@click="choices.{{ $cat['id'] }} = ! choices.{{ $cat['id'] }}">
									<span class="rg-toggle-knob"></span>
									<span class="rg-toggle-text" x-text="choices.{{ $cat['id'] }} ? 'Be' : 'Ki'"></span>
								</button>
							@endif
						</div>
						@if (count($cat['cookies']))
							<button type="button" class="rg-cookie-cat-detail-btn" :class="{ 'is-open': open }" :aria-expanded="open" @click="open = ! open">
								<i data-lucide="chevron-down" class="lucide-sm"></i>
								<span x-text="open ? 'Részletek elrejtése' : 'Sütik megtekintése ({{ count($cat['cookies']) }})'">Sütik megtekintése ({{ count($cat['cookies']) }})</span>
							</button>
							<div x-show="open" x-cloak class="rg-cookie-table-wrap">
								<table class="rg-cookie-table">
									<thead>
										<tr><th>Süti</th><th>Szolgáltató</th><th>Cél</th><th>Lejárat</th></tr>
									</thead>
									<tbody>
										@foreach ($cat['cookies'] as $cookie)
											<tr>
												<td><code>{{ $cookie[0] }}</code></td>
												<td>{{ $cookie[1] }}</td>
												<td>{{ $cookie[2] }}</td>
												<td>{{ $cookie[3] }}</td>
											</tr>
										@endforeach
									</tbody>
								</table>
							</div>
						@else
							<p class="rg-cookie-empty">Ebben a kategóriában jelenleg nem helyezünk el sütit; a kategória a jövőbeni bővítésekhez van előkészítve.</p>
						@endif
					</div>
				@endforeach
			</div>

			<div class="rg-cookie-modal-foot">
				<button type="button" class="rt-btn rt-btn-secondary" @click="rejectAll()">Csak a szükségesek</button>
				<div class="rg-cookie-modal-foot-right">
					<button type="button" class="rt-btn rt-btn-secondary" @click="saveSelected()">Kiválasztottak mentése</button>
					<button type="button" class="rt-btn rt-btn-primary" @click="acceptAll()">Összes elfogadása</button>
				</div>
			</div>
		</div>
	</div>
</div>
