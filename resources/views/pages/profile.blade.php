{{--
	Profile & settings (Profil & beállítások) - ported from portal-profile.jsx.
	3 tabs (personal / security / notifications) + account meta. The active tab is
	reflected in the URL (?tab=); each form is a REAL POST that redirects back to
	its tab with a pt_alert flash (so the browser can offer to save a new password).
	Data is the signed-in cus_users account (name/email/phone/birth_date + the
	notification toggles); the e-mail is read-only (it is the login id).
--}}
@extends('layouts.portal')

@section('title', 'Profil & beállítások - Royal Telekom Ügyfélkapu')

@section('content')

	@php
		$nameParts = explode(' ', trim($user['name']));
		$last  = $nameParts[0] ?? '';
		$first = $nameParts[1] ?? '';
		$activeCount = count(array_filter($contracts, fn ($c) => $c['status'] === 'active'));
	@endphp

	<div class="p-page"
	     x-data="{
	         tab: 'personal',
	         go(t) { this.tab = t; history.replaceState(null, '', '?tab=' + t); },
	         init() { const t = new URLSearchParams(window.location.search).get('tab'); if (['personal','security','notif'].includes(t)) this.tab = t; }
	     }">

		<div class="p-tabs">
			<button type="button" :class="{ on: tab === 'personal' }" @click="go('personal')"><i data-lucide="user" class="lucide-xs"></i> Személyes adatok</button>
			<button type="button" :class="{ on: tab === 'security' }" @click="go('security')"><i data-lucide="shield" class="lucide-xs"></i> Jelszó & biztonság</button>
			<button type="button" :class="{ on: tab === 'notif' }" @click="go('notif')"><i data-lucide="bell" class="lucide-xs"></i> Értesítések</button>
		</div>

		{{-- PERSONAL --}}
		<div class="p-card p-pad" x-show="tab === 'personal'">
			<div class="p-section-title">
				<div><div class="eyebrow">Személyes adatok</div><h3>Az Ön szerződéskötéskor megadott adatai</h3></div>
			</div>
			<form class="p-profile-form" method="POST" action="{{ route('profile.personal') }}">
				@csrf
				<div class="p-field-row">
					<div class="p-field"><label for="pf_last">Vezetéknév</label><input id="pf_last" name="last_name" class="rt-input" value="{{ old('last_name', $last) }}" required></div>
					<div class="p-field"><label for="pf_first">Keresztnév</label><input id="pf_first" name="first_name" class="rt-input" value="{{ old('first_name', $first) }}" required></div>
				</div>
				<div class="p-field-row">
					<div class="p-field">
						<label for="pf_email">E-mail cím</label>
						<input id="pf_email" class="rt-input" type="email" value="{{ $user['email'] }}" readonly>
						<span class="hint">Az e-mail cím a belépési azonosító, ezért itt nem módosítható.</span>
					</div>
					<div class="p-field"><label for="pf_phone">Telefonszám</label><input id="pf_phone" name="phone" class="rt-input" type="tel" value="{{ old('phone', $profile['phone'] ?? '') }}" required></div>
				</div>
				<div class="p-field"><label for="pf_dob">Születési dátum</label><input id="pf_dob" name="birth_date" class="rt-input" type="date" value="{{ old('birth_date', $profile['birth_date'] ?? '') }}"></div>
				<div class="p-profile-actions">
					<button type="submit" class="rt-btn rt-btn-primary"><i data-lucide="check" class="lucide-xs"></i> Mentés</button>
				</div>
			</form>
		</div>

		{{-- SECURITY --}}
		<div class="p-card p-pad" x-show="tab === 'security'" x-cloak
		     x-data="{ pw: '', pw2: '', labels: ['Túl gyenge','Gyenge','Közepes','Erős','Kiváló'], colors: ['#9F2B1F','#C0673B','#8B6A1F','#3F7A4A','#2B5F38'],
		               get sc() { let n = 0; if (this.pw.length >= 10) n++; if (/[A-Z]/.test(this.pw)) n++; if (/[0-9]/.test(this.pw)) n++; if (/[^A-Za-z0-9]/.test(this.pw)) n++; return n; },
		               check(e) {
		                   if (this.pw !== this.pw2) { e.preventDefault(); window.ptAlert({ variant: 'error', title: 'A jelszavak nem egyeznek', message: 'A két megadott jelszó nem azonos. Kérjük, ellenőrizze és próbálja újra.' }); return; }
		                   if (this.sc < 3) { e.preventDefault(); window.ptAlert({ variant: 'error', title: 'Túl gyenge jelszó', message: 'A biztonság érdekében legalább „Erős” szintű jelszót adjon meg - 10+ karakter, nagybetűvel és számmal.' }); return; }
		               } }">
			<div class="p-section-title">
				<div><div class="eyebrow">Jelszó</div><h3>Jelszó módosítása</h3></div>
			</div>
			<form class="p-profile-form" method="POST" action="{{ route('profile.password') }}" @submit="check($event)">
				@csrf
				<input type="email" name="username" value="{{ $user['email'] }}" autocomplete="username" hidden>
				<div class="p-field">
					<label for="sec_cur">Jelenlegi jelszó</label>
					<input id="sec_cur" name="current_password" class="rt-input" type="password" autocomplete="current-password" required placeholder="••••••••">
				</div>
				<div class="p-field-row">
					<div class="p-field">
						<label for="sec_pw">Új jelszó</label>
						<input id="sec_pw" name="password" class="rt-input" type="password" autocomplete="new-password" required x-model="pw" placeholder="Min. 10 karakter">
						<div class="pw-strength" x-show="pw" x-cloak>
							<div class="bar"><div class="fill" :style="'--pw:' + (sc / 4 * 100) + '%; --pwc:' + colors[sc]"></div></div>
							<span :style="'color:' + colors[sc]" x-text="labels[sc]"></span>
						</div>
						<span class="hint">Ajánlott: 10+ karakter, nagybetű, szám, és speciális karakter.</span>
					</div>
					<div class="p-field">
						<label for="sec_pw2">Új jelszó megerősítése</label>
						<input id="sec_pw2" name="password_confirmation" class="rt-input" type="password" autocomplete="new-password" required x-model="pw2" placeholder="••••••••">
						<span class="hint p-hint-ok" x-show="pw2 && pw && pw === pw2" x-cloak><i data-lucide="check" class="lucide-xs"></i> A jelszavak egyeznek.</span>
						<span class="hint p-hint-bad" x-show="pw2 && pw && pw !== pw2" x-cloak>A jelszavak nem egyeznek.</span>
					</div>
				</div>
				<div class="p-profile-actions">
					<button type="submit" class="rt-btn rt-btn-primary"><i data-lucide="shield" class="lucide-xs"></i> Jelszó módosítása</button>
				</div>
			</form>
		</div>

		{{-- NOTIFICATIONS --}}
		<div class="p-card p-pad" x-show="tab === 'notif'" x-cloak>
			<div class="p-section-title">
				<div><div class="eyebrow">Értesítések</div><h3>Hogyan értesítsük Önt?</h3></div>
			</div>
			<form class="p-profile-form" method="POST" action="{{ route('profile.notifications') }}">
				@csrf
				<div class="p-notif-list">
					<div class="p-toggle-row">
						<div>
							<strong>Szolgáltatás-kimaradás</strong>
							<p>Tervezett karbantartás vagy hibajavítás.</p>
						</div>
						<label class="p-switch">
							<input type="checkbox" name="outage" value="1" @checked($notify['outage'])>
							<span></span>
						</label>
					</div>
					<div class="p-toggle-row">
						<div>
							<strong>Akciók és újdonságok</strong>
							<p>Csak a témánkba illő, nem kéretlen.</p>
						</div>
						<label class="p-switch">
							<input type="checkbox" name="promo" value="1" @checked($notify['promo'])>
							<span></span>
						</label>
					</div>
				</div>
				<div class="p-profile-actions">
					<button type="submit" class="rt-btn rt-btn-primary"><i data-lucide="check" class="lucide-xs"></i> Mentés</button>
				</div>
			</form>
		</div>

		{{-- ACCOUNT META --}}
		<div class="p-card p-pad">
			<div class="p-section-title">
				<div><div class="eyebrow">Fiók</div><h3>Fiók-adatok</h3></div>
			</div>
			<div class="p-account-meta">
				<div><span>Ügyfél-azonosító</span><strong>#{{ $user['customerId'] }}</strong></div>
				<div><span>Regisztráció</span><strong>{{ $user['memberSince'] }}</strong></div>
				<div><span>Aktív szerződések</span><strong>{{ $activeCount }} db</strong></div>
			</div>
		</div>

	</div>

@endsection
