{{--
	Portal topbar - burger (mobile) + greeting + page title + actions.
	Actions: notifications bell-popover (Alpine) + settings (-> profile).
	Mirrors portal-app.jsx Topbar. Notification read-state is server-rendered
	(from $notifs); marking read persists once the backend lands.
--}}
@php
	$titles = [
		'dashboard' => 'Főoldal',
		'invoices'  => 'Számláim',
		'plans'     => 'Szerződéseim',
		'docs'      => 'Dokumentumok',
		'profile'   => 'Profil & beállítások',
	];
	$current   = Route::currentRouteName();
	$pageTitle = $titles[$current] ?? 'Ügyfélkapu';
	$hour      = (int) now()->format('G');
	$greet     = $hour < 10 ? 'Jó reggelt' : ($hour < 18 ? 'Jó napot' : 'Jó estét');
	$firstName = ($user ?? null) ? (\explode(' ', $user['name'])[1] ?? $user['name']) : 'Ügyfél';
	$notifs    = $notifs ?? [];
	$unread    = \count(\array_filter($notifs, fn ($n) => empty($n['read'])));
@endphp

<header class="p-topbar">
	<button type="button" class="p-topbar-burger" aria-label="Menü" @click="navOpen = true">
		<i data-lucide="menu" class="lucide-md"></i>
	</button>

	<div>
		<div class="greeting">{{ $greet }}, {{ $firstName }}!</div>
		<h1>{{ $pageTitle }}</h1>
	</div>

	<div class="actions" x-data="{ bell: false }">
		<div class="p-bell-wrap">
			<button type="button" class="icon-btn" :class="{ 'is-on': bell }" @click="bell = !bell" aria-label="Értesítések">
				<i data-lucide="bell" class="lucide-sm"></i>
				@if ($unread > 0)
					<span class="dot"></span>
				@endif
			</button>

			<div class="p-bell-scrim" x-show="bell" x-cloak @click="bell = false"></div>
			<div class="p-bell-pop" x-show="bell" x-cloak x-transition>
				<div class="head">
					<div class="head-txt">
						<strong>Értesítések</strong>
						@if ($unread > 0)
							<span class="cnt">{{ $unread }} olvasatlan</span>
						@endif
					</div>
					<button type="button" class="mark-all" @click="bell = false" @disabled($unread === 0)>
						<i data-lucide="check" class="lucide-xs"></i> Mindet olvasottnak jelölöm
					</button>
				</div>
				<div class="list">
					@foreach (array_slice($notifs, 0, 10) as $n)
						<a href="{{ route($n['route']) }}" class="item {{ empty($n['read']) ? 'unread' : '' }}">
							<span class="ico" data-tone="{{ $n['tone'] }}"><i data-lucide="{{ $n['icon'] }}" class="lucide-sm"></i></span>
							<span class="txt">
								<span class="t">{{ $n['title'] }}</span>
								<span class="b">{{ $n['body'] }}</span>
								<span class="time">{{ $n['time'] }}</span>
							</span>
							@if (empty($n['read']))
								<span class="unread-dot"></span>
							@endif
						</a>
					@endforeach
				</div>
			</div>
		</div>

		<a href="{{ route('profile') }}" class="icon-btn" aria-label="Profil & beállítások" title="Profil & beállítások">
			<i data-lucide="settings" class="lucide-sm"></i>
		</a>
	</div>
</header>
