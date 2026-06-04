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
	<link rel="icon" type="image/svg+xml" href="{{ asset('assets/crest-monogram.svg') }}">
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
			<div class="small">© {{ date('Y') }} Royal Telekom · Royal Group · Minőség és diszkréció 1998 óta.</div>
		</aside>

		{{-- Form panel --}}
		<div class="p-auth-form-wrap">
			@yield('content')
		</div>
	</div>

	<script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js" defer></script>
	<script>
		window.addEventListener('DOMContentLoaded', () => {
			if (window.lucide) lucide.createIcons();
		});
	</script>
</body>
</html>
