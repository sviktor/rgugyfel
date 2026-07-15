{{--
	Portal layout - authenticated customer area.
	Sidebar (nav + user box) + Topbar (greeting + actions) + Main.
	The mobile drawer scrim uses Alpine.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>@yield('title', config('app.name'))</title>
	{{-- Favicons (Royal crest kit - files in public/assets/) --}}
	<link rel="icon" href="{{ asset('assets/favicon.ico') }}" sizes="any">
	<link rel="icon" type="image/svg+xml" href="{{ asset('assets/favicon.svg') }}">
	<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('assets/favicon-32x32.png') }}">
	<link rel="icon" type="image/png" sizes="16x16" href="{{ asset('assets/favicon-16x16.png') }}">
	<link rel="apple-touch-icon" sizes="180x180" href="{{ asset('assets/apple-touch-icon.png') }}">
	<link rel="manifest" href="{{ asset('assets/site.webmanifest') }}">
	<meta name="theme-color" content="#0E2A47">
	@vite(['resources/css/app.css', 'resources/js/app.js'])
	{{-- Google Analytics (gtag.js) - measurement id from the CMS
	     (rgadmin: WEBOLDALAK -> Ügyfélkapu -> Beállítások -> Analitika).
	     GDPR: only emitted once the visitor opted into statistics cookies (the
	     consent banner JS injects gtag itself right after a first-visit opt-in). --}}
	@if ($cms->get('global.analytics.ga_id') !== '' && \App\Support\CookieConsent::allows('statistics'))
		<script async src="https://www.googletagmanager.com/gtag/js?id={{ $cms->get('global.analytics.ga_id') }}"></script>
		<script>
			window.dataLayer = window.dataLayer || [];
			function gtag(){dataLayer.push(arguments);}
			gtag('js', new Date());
			gtag('config', '{{ $cms->get('global.analytics.ga_id') }}');
		</script>
	@endif
	@stack('head')
</head>
<body>

	{{-- Preview mode: the Ügyfélkapu is switched OFF but this session entered
	     with the preview key (SiteAvailability middleware shares the flag). --}}
	@if (! empty($siteOffline))
		<div class="pt-offline-banner">Az Ügyfélkapu jelenleg ki van kapcsolva.</div>
	@endif

	<div class="p-shell" x-data="{ navOpen: false }">

		{{-- Scrim for mobile sidebar overlay --}}
		<div class="p-sidebar-scrim" :class="{ 'is-open': navOpen }" @click="navOpen = false"></div>

		@include('partials.sidebar')

		<main class="p-main">
			@include('partials.topbar')

			@yield('content')
		</main>
	</div>

	{{-- GDPR cookie consent banner + settings modal --}}
	@include('partials.cookie-consent')

	{{-- Lightbox alert (window.ptAlert) --}}
	@include('partials._pt-alert')

	<script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js" defer></script>
	<script>
		window.addEventListener('DOMContentLoaded', () => {
			if (window.lucide) lucide.createIcons();
			// IMPORTANT: match the *placeholder* <i> only. createIcons() outputs
			// <svg data-lucide="..."> nodes, so a bare [data-lucide] matcher sees
			// its own output as "new icons" and loops forever (page freeze).
			new MutationObserver((muts) => {
				if (!window.lucide) return;
				for (const m of muts) {
					for (const n of m.addedNodes) {
						if (n.nodeType === 1 && (n.matches?.('i[data-lucide]') || n.querySelector?.('i[data-lucide]'))) {
							lucide.createIcons();
							return;
						}
					}
				}
			}).observe(document.body, { childList: true, subtree: true });
		});
	</script>

	@stack('scripts')

	{{-- Flash -> ptAlert bridge (e.g. the contract-request confirmation). --}}
	@if (session('pt_alert'))
		<script>
			window.addEventListener('DOMContentLoaded', function () {
				if (window.ptAlert) window.ptAlert(@json(session('pt_alert')));
			});
		</script>
	@endif

	{{-- Validation errors -> ptAlert bridge (full-page POST forms, e.g. /profil). --}}
	@if ($errors->any())
		<script>
			window.addEventListener('DOMContentLoaded', function () {
				if (window.ptAlert) window.ptAlert({
					variant: 'error',
					title: @json(__('Hiányzó vagy hibás adatok')),
					message: @json(implode("\n", $errors->all())),
				});
			});
		</script>
	@endif
</body>
</html>
