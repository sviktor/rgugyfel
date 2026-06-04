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
	<link rel="icon" type="image/svg+xml" href="{{ asset('assets/crest-monogram.svg') }}">
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
