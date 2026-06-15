{{--
	Profile & settings (Profil & beállítások) - ported from portal-profile.jsx.
	4 tabs (personal / address / security / notifications) + account meta.
	Tabs via Alpine; saving pops the shared ptAlert toast (no backend yet -
	the real persistence is the programming phase). Mock data via PortalMockData.
--}}
@extends('layouts.portal')

@section('title', 'Profil & beállítások - Royal Telekom Ügyfélkapu')

@section('content')

	@php
		$nameParts = explode(' ', $user['name']);
		$last  = $nameParts[0] ?? 'Kis';
		$first = $nameParts[1] ?? 'Éva';
		$activeCount = count(array_filter($contracts, fn ($c) => $c['status'] === 'active'));
	@endphp

	<div class="p-page" x-data="{ tab: 'personal' }">

		<div class="p-tabs">
			<button type="button" :class="{ on: tab === 'personal' }" @click="tab = 'personal'"><i data-lucide="user" class="lucide-xs"></i> Személyes adatok</button>
			<button type="button" :class="{ on: tab === 'security' }" @click="tab = 'security'"><i data-lucide="shield" class="lucide-xs"></i> Jelszó & biztonság</button>
			<button type="button" :class="{ on: tab === 'notif' }" @click="tab = 'notif'"><i data-lucide="bell" class="lucide-xs"></i> Értesítések</button>
		</div>

		{{-- PERSONAL --}}
		<div class="p-card p-pad" x-show="tab === 'personal'">
			<div class="p-section-title">
				<div><div class="eyebrow">Személyes adatok</div><h3>Az Ön szerződéskötéskor megadott adatai</h3></div>
			</div>
			<form class="p-profile-form" @submit.prevent="window.ptAlert({ variant: 'info', title: 'Adatok elmentve', message: 'Személyes adatait sikeresen elmentettük. A változások a következő számlán is megjelennek.' })">
				<div class="p-field-row">
					<div class="p-field"><label for="pf_last">Vezetéknév</label><input id="pf_last" class="rt-input" value="{{ $last }}"></div>
					<div class="p-field"><label for="pf_first">Keresztnév</label><input id="pf_first" class="rt-input" value="{{ $first }}"></div>
				</div>
				<div class="p-field-row">
					<div class="p-field">
						<label for="pf_email">E-mail cím</label>
						<input id="pf_email" class="rt-input" type="email" value="{{ $user['email'] }}">
						<span class="hint">Erre küldjük a számlákat és értesítéseket.</span>
					</div>
					<div class="p-field"><label for="pf_phone">Telefonszám</label><input id="pf_phone" class="rt-input" type="tel" value="+36 30 555 0382"></div>
				</div>
				<div class="p-field"><label for="pf_dob">Születési dátum</label><input id="pf_dob" class="rt-input" type="date" value="1985-04-22"></div>
				<div class="p-profile-actions">
					<button type="button" class="rt-btn rt-btn-secondary">Mégsem</button>
					<button type="submit" class="rt-btn rt-btn-primary"><i data-lucide="check" class="lucide-xs"></i> Mentés</button>
				</div>
			</form>
		</div>

		{{-- SECURITY --}}
		<div class="p-card p-pad" x-show="tab === 'security'" x-cloak
		     x-data="{ pw: '', pw2: '', labels: ['Túl gyenge','Gyenge','Közepes','Erős','Kiváló'], colors: ['#9F2B1F','#C0673B','#8B6A1F','#3F7A4A','#2B5F38'],
		               get sc() { let n = 0; if (this.pw.length >= 10) n++; if (/[A-Z]/.test(this.pw)) n++; if (/[0-9]/.test(this.pw)) n++; if (/[^A-Za-z0-9]/.test(this.pw)) n++; return n; },
		               save() { if (this.pw !== this.pw2) { window.ptAlert({ variant: 'error', title: 'A jelszavak nem egyeznek', message: 'A két megadott jelszó nem azonos. Kérjük, ellenőrizze és próbálja újra.' }); return; } if (this.sc < 3) { window.ptAlert({ variant: 'error', title: 'Túl gyenge jelszó', message: 'A biztonság érdekében legalább „Erős” szintű jelszót adjon meg - 10+ karakter, nagybetűvel és számmal.' }); return; } this.pw = ''; this.pw2 = ''; window.ptAlert({ variant: 'info', title: 'Jelszó módosítva', message: 'Új jelszavát sikeresen elmentettük. A következő belépéskor már az új jelszavával lépjen be.' }); } }">
			<div class="p-section-title">
				<div><div class="eyebrow">Jelszó</div><h3>Jelszó módosítása</h3></div>
			</div>
			<form class="p-profile-form" @submit.prevent="save()">
				<div class="p-field">
					<label for="sec_cur">Jelenlegi jelszó</label>
					<input id="sec_cur" class="rt-input" type="password" required placeholder="••••••••">
				</div>
				<div class="p-field-row">
					<div class="p-field">
						<label for="sec_pw">Új jelszó</label>
						<input id="sec_pw" class="rt-input" type="password" required x-model="pw" placeholder="Min. 10 karakter">
						<div class="pw-strength" x-show="pw" x-cloak>
							<div class="bar"><div class="fill" :style="'--pw:' + (sc / 4 * 100) + '%; --pwc:' + colors[sc]"></div></div>
							<span :style="'color:' + colors[sc]" x-text="labels[sc]"></span>
						</div>
						<span class="hint">Ajánlott: 10+ karakter, nagybetű, szám, és speciális karakter.</span>
					</div>
					<div class="p-field">
						<label for="sec_pw2">Új jelszó megerősítése</label>
						<input id="sec_pw2" class="rt-input" type="password" required x-model="pw2" placeholder="••••••••">
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
			<div class="p-notif-list">
				@php
					$toggles = [
						['Szolgáltatás-kimaradás', 'Tervezett karbantartás vagy hibajavítás.', true],
						['Akciók és újdonságok', 'Csak a témánkba illő, nem kéretlen.', false],
					];
				@endphp
				@foreach ($toggles as [$t, $d, $on])
					<div class="p-toggle-row">
						<div>
							<strong>{{ $t }}</strong>
							<p>{{ $d }}</p>
						</div>
						<label class="p-switch">
							<input type="checkbox" @checked($on)>
							<span></span>
						</label>
					</div>
				@endforeach
			</div>
			<div class="p-profile-actions">
				<button type="button" class="rt-btn rt-btn-primary" @click="window.ptAlert({ variant: 'info', title: 'Adatok elmentve', message: 'Értesítési beállításait sikeresen elmentettük.' })"><i data-lucide="check" class="lucide-xs"></i> Mentés</button>
			</div>
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
