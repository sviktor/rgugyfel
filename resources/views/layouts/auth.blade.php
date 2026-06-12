{{--
	Auth screens layout — login, register, forgot.
	Split-screen: dark navy side (brand + headline) + form panel.
	Visual mockup only — no auth backend yet (customers table comes
	from rgadmin once that migration lands).
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
</head>
<body>
	<div class="p-auth">

		{{-- Dark navy side with crest watermark + headline --}}
		<aside class="p-auth-side">
			<img class="crest-bg" src="{{ asset('assets/crest-monogram.svg') }}" alt="" aria-hidden="true">
			<div class="logo-row">
				<img class="crest" src="{{ asset('assets/crest-monogram.svg') }}" alt="">
				<div class="lockup">
					<div class="top">ROYAL TELEKOM</div>
					<div class="bottom">@yield('side-eyebrow', 'ÜGYFÉLKAPU')</div>
				</div>
			</div>
			<div style="max-width:460px;">
				<h1>@yield('side-headline')</h1>
				<p class="lede">@yield('side-lede')</p>
			</div>
			<div class="small">© {{ date('Y') }} Royal Telekom · Royal Group · Minőség és diszkréció 1998 óta. · <a href="#" data-cookie-settings>Sütibeállítások</a></div>
		</aside>

		{{-- Form panel --}}
		<div class="p-auth-form-wrap">
			@yield('content')
		</div>
	</div>

	{{-- GDPR cookie consent banner + settings modal --}}
	@include('partials.cookie-consent')

	<script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js" defer></script>
	<script>
		window.addEventListener('DOMContentLoaded', () => {
			if (window.lucide) lucide.createIcons();
		});
	</script>
</body>
</html>
