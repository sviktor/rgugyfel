{{--
	Login screen - ported from portal-auth.jsx LoginScreen.
	Mockup form: POSTs to /login and fake-redirects to /dashboard.
--}}
@extends('layouts.auth')

@section('title', 'Belépés - Royal Telekom Ügyfélkapu')
@section('side-headline', 'Üdvözöljük újra, kedves Ügyfelünk.')
@section('side-lede', 'Itt megtekintheti aktuális számláit, kezelheti előfizetését, bejelentheti hibáit, és felveheti a kapcsolatot ügyfélszolgálatunkkal - mindezt egyetlen helyen, biztonságosan.')

@section('content')

	<form class="p-auth-form" method="POST" action="{{ route('login.submit') }}" data-auth-form novalidate>
		@csrf
		<h2>Belépés</h2>
		<p class="sub">Adja meg e-mail címét vagy ügyfél-azonosítóját.</p>

		<div class="p-auth-form-grid">
			<div class="p-field">
				<label>E-mail vagy ügyfél-azonosító</label>
				<input class="rt-input" type="text" name="login" value="{{ old('login') }}"
				       placeholder="kis.eva@example.hu" autofocus>
			</div>

			<div class="p-field">
				<label class="p-auth-label-row">
					<span>Jelszó</span>
					<a href="{{ route('forgot') }}" class="p-auth-forgot">Elfelejtette?</a>
				</label>
				<input class="rt-input" type="password" name="password" placeholder="••••••••">
			</div>

			<label class="p-auth-remember">
				<input type="checkbox" name="remember" value="1" checked>
				Maradjak bejelentkezve ezen a gépen
			</label>

			<button type="submit" class="rt-btn rt-btn-primary rt-btn-large p-auth-submit">
				Belépés
			</button>
		</div>

		<hr class="p-auth-sep">

		<div class="p-auth-switch">
			Még nem ügyfelünk?
			<a href="{{ route('register') }}">Regisztráció <i data-lucide="arrow-right" class="lucide-xs"></i></a>
		</div>
	</form>

@endsection
