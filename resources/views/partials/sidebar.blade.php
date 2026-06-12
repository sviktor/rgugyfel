{{--
	Portal sidebar - nav groups + user box.
	NAV groups + items mirror portal-app.jsx NAV constant.
--}}
@php
	$nav = [
		['group' => 'Áttekintés', 'items' => [
			['route' => 'dashboard', 'label' => 'Főoldal',                 'icon' => 'home'],
		]],
		['group' => 'Pénzügy', 'items' => [
			['route' => 'invoices',  'label' => 'Számláim',                'icon' => 'receipt',       'badge' => $badges['invoices'] ?? null],
		]],
		['group' => 'Szolgáltatásaim', 'items' => [
			['route' => 'plans',     'label' => 'Szerződéseim',            'icon' => 'package'],
			['route' => 'usage',     'label' => 'Forgalom & sebesség',     'icon' => 'activity'],
		]],
		['group' => 'Ügyintézés', 'items' => [
			['route' => 'tickets',   'label' => 'Hibabejelentés',          'icon' => 'alert-circle',  'badge' => $badges['tickets'] ?? null],
			['route' => 'docs',      'label' => 'Dokumentumok',            'icon' => 'file-text'],
		]],
		['group' => 'Fiók', 'items' => [
			['route' => 'profile',   'label' => 'Profil & beállítások',    'icon' => 'user'],
		]],
	];
	$current = Route::currentRouteName();
	$user    = $user ?? ['name' => 'Vendég', 'initials' => 'V', 'customerId' => '-'];
@endphp

<aside class="p-sidebar" :class="{ 'is-mobile-open': navOpen }">
	<div class="brand">
		<img src="{{ asset('assets/crest-monogram.svg') }}" alt="">
		<div class="lockup">
			<div class="top">ROYAL TELEKOM</div>
			<div class="bottom">ÜGYFÉLKAPU</div>
		</div>
		<button type="button" class="p-sidebar-close" aria-label="Bezárás" @click="navOpen = false">
			<i data-lucide="x" class="lucide-md"></i>
		</button>
	</div>

	<nav>
		@foreach ($nav as $group)
			<div class="group-title">{{ $group['group'] }}</div>
			@foreach ($group['items'] as $item)
				<a href="{{ route($item['route']) }}" class="{{ $current === $item['route'] ? 'active' : '' }}">
					<i data-lucide="{{ $item['icon'] }}" class="lucide-sm"></i>
					<span>{{ $item['label'] }}</span>
					@if (! empty($item['badge']))
						<span class="badge-pill">{{ $item['badge'] }}</span>
					@endif
				</a>
			@endforeach
		@endforeach
	</nav>

	<div class="userbox">
		<div class="avatar">{{ $user['initials'] }}</div>
		<div class="who">
			<div class="name">{{ $user['name'] }}</div>
			<div class="id">#{{ $user['customerId'] }}</div>
		</div>
		<form action="{{ route('logout') }}" method="POST" style="display:contents;">
			@csrf
			<button type="submit" class="logout" aria-label="Kijelentkezés" title="Kijelentkezés">
				<i data-lucide="log-out" class="lucide-sm"></i>
			</button>
		</form>
	</div>

	{{-- Cookie-settings reopen link (GDPR: must stay reachable after consent) --}}
	<div class="p-sidebar-legal">
		<a href="#" data-cookie-settings>Sütibeállítások</a>
	</div>
</aside>
