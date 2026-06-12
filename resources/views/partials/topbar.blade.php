{{--
	Portal topbar - burger (mobile) + greeting + page title + actions.
--}}
@php
	$titles = [
		'dashboard' => 'Főoldal',
		'invoices'  => 'Számláim',
		'plans'     => 'Szerződéseim',
		'usage'     => 'Forgalom & sebesség',
		'tickets'   => 'Hibabejelentés',
		'docs'      => 'Dokumentumok',
		'profile'   => 'Profil & beállítások',
	];
	$current   = Route::currentRouteName();
	$pageTitle = $titles[$current] ?? 'Ügyfélkapu';
	$hour      = (int) now()->format('G');
	$greet     = $hour < 10 ? 'Jó reggelt' : ($hour < 18 ? 'Jó napot' : 'Jó estét');
	$firstName = ($user ?? null) ? \explode(' ', $user['name'])[1] ?? $user['name'] : 'Ügyfél';
@endphp

<header class="p-topbar">
	<button type="button" class="p-topbar-burger" aria-label="Menü" @click="navOpen = true">
		<i data-lucide="menu" class="lucide-md"></i>
	</button>
	<div>
		<div class="greeting">{{ $greet }}, {{ $firstName }}!</div>
		<h1>{{ $pageTitle }}</h1>
	</div>
	<div class="actions">
		<button type="button" class="icon-btn" aria-label="Értesítések">
			<i data-lucide="bell" class="lucide-sm"></i>
			<span class="dot"></span>
		</button>
		<button type="button" class="icon-btn" aria-label="Beállítások">
			<i data-lucide="settings" class="lucide-sm"></i>
		</button>
	</div>
</header>
