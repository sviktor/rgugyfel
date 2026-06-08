{{--
	Portal layout — authenticated customer area.
	Sidebar (nav + user box) + Topbar (greeting + actions) + Main.
	The mobile drawer scrim uses Alpine.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>@yield('title', config('app.name'))</title>
	{{-- Favicons (Royal crest kit — files in public/assets/) --}}
	<link rel="icon" href="{{ asset('assets/favicon.ico') }}" sizes="any">
	<link rel="icon" type="image/svg+xml" href="{{ asset('assets/favicon.svg') }}">
	<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('assets/favicon-32x32.png') }}">
	<link rel="icon" type="image/png" sizes="16x16" href="{{ asset('assets/favicon-16x16.png') }}">
	<link rel="apple-touch-icon" sizes="180x180" href="{{ asset('assets/apple-touch-icon.png') }}">
	<link rel="manifest" href="{{ asset('assets/site.webmanifest') }}">
	<meta name="theme-color" content="#0E2A47">
	@vite(['resources/css/app.css', 'resources/js/app.js'])
	@stack('head')
</head>
<body>

	<div class="p-shell" x-data="{ navOpen: false }">

		{{-- Scrim for mobile sidebar overlay --}}
		<div class="p-sidebar-scrim" :class="{ 'is-open': navOpen }" @click="navOpen = false"></div>

		@include('partials.sidebar')

		<main class="p-main">
			@include('partials.topbar')

			@yield('content')
		</main>
	</div>

	<script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js" defer></script>
	<script>
		window.addEventListener('DOMContentLoaded', () => {
			if (window.lucide) lucide.createIcons();
			new MutationObserver((muts) => {
				if (!window.lucide) return;
				for (const m of muts) {
					for (const n of m.addedNodes) {
						if (n.nodeType === 1 && (n.matches?.('[data-lucide]') || n.querySelector?.('[data-lucide]'))) {
							lucide.createIcons();
							return;
						}
					}
				}
			}).observe(document.body, { childList: true, subtree: true });
		});
	</script>

	@stack('scripts')
</body>
</html>
