{{--
	Maintenance page - shown (with a 503 status) to every visitor while the
	Ügyfélkapu is switched OFF in rgadmin (WEBOLDALAK -> Ügyfélkapu -> Beállítások ->
	Oldal elérhetőség). Admins can still browse via the preview key - see
	App\Http\Middleware\SiteAvailability. Standalone (no sidebar/footer).
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>{{ config('app.name') }} - Karbantartás</title>
	<meta name="robots" content="noindex">

	{{-- Favicons (Royal crest kit - files in public/assets/) --}}
	<link rel="icon" href="{{ asset('assets/favicon.ico') }}" sizes="any">
	<link rel="icon" type="image/svg+xml" href="{{ asset('assets/favicon.svg') }}">
	<meta name="theme-color" content="#0E2A47">

	@vite(['resources/css/app.css'])
</head>
<body>
	<main class="pt-maintenance">
		<div class="pt-maintenance-box">
			<img src="{{ asset('assets/royaltelekom-logo.svg') }}" alt="Royal Telekom" class="pt-maintenance-logo">
			<h1>Karbantartás alatt</h1>
			<p>Az Ügyfélkapu jelenleg nem elérhető, kérünk, nézz vissza később.</p>
		</div>
	</main>
</body>
</html>
