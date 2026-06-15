{{--
	Public content layout - a simple, guest-accessible page chrome (brand bar +
	content + slim footer) for standalone pages like the legal documents
	(ÁSZF / Adatvédelem). Lighter than the portal shell (no sidebar, no auth).
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
	<header class="p-page-top">
		<div class="rt-container p-page-top-inner">
			<a href="{{ route('login') }}" class="p-page-brand">
				<span class="top">ROYAL TELEKOM</span>
				<span class="bottom">Ügyfélkapu</span>
			</a>
			<a href="{{ route('login') }}" class="rt-btn rt-btn-secondary">
				<i data-lucide="arrow-left" class="lucide-sm"></i> Belépés
			</a>
		</div>
	</header>

	<main class="p-page-main">
		@yield('content')
	</main>

	<footer class="p-page-foot">
		<div class="rt-container">
			© {{ date('Y') }} Royal Telekom · Royal Group · <a href="#" data-cookie-settings>Sütibeállítások</a>
		</div>
	</footer>

	{{-- GDPR cookie consent banner + settings modal --}}
	@include('partials.cookie-consent')

	{{-- Lightbox alert (window.ptAlert) --}}
	@include('partials._pt-alert')

	<script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js" defer></script>
	<script>
		window.addEventListener('DOMContentLoaded', () => {
			if (window.lucide) lucide.createIcons();
		});
	</script>

	@stack('scripts')
</body>
</html>
