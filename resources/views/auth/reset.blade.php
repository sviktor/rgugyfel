{{--
	Reset - the set-new-password screen reached from the password-reset e-mail.
	AJAX submit (data-auth-form) -> PasswordResetController::reset; on success the
	controller redirects to login with a ptAlert confirmation. The token + email
	travel as hidden fields (the broker validates them).
--}}
@extends('layouts.auth')

@section('title', 'Új jelszó - Royal Telekom Ügyfélkapu')
@section('side-eyebrow', $cms->get('home.login_page.reset_eyebrow') ?: 'JELSZÓ-HELYREÁLLÍTÁS')
@section('side-headline', $cms->get('home.login_page.reset_headline') ?: 'Állítson be egy új jelszót.')
@section('side-lede', $cms->get('home.login_page.reset_lede') ?: 'Adjon meg egy erős, új jelszót. A beállítás után azonnal beléphet vele a fiókjába.')

@section('content')

	<form class="p-auth-form" method="POST" action="{{ route('password.update') }}" data-auth-form novalidate>
		@csrf
		<input type="hidden" name="token" value="{{ $token }}">
		<input type="hidden" name="email" value="{{ $email }}">

		<h2>Új jelszó beállítása</h2>
		<p class="sub">Válasszon egy biztonságos jelszót a fiókjához.</p>

		<div class="p-auth-form-grid">
			<div class="p-field">
				<label>E-mail cím</label>
				<input class="rt-input" type="email" value="{{ $email }}" readonly>
			</div>
			<div class="p-field">
				<label>Új jelszó</label>
				<input class="rt-input" type="password" name="password" placeholder="Min. 10 karakter">
				<span class="hint">Legalább 10 karakter, tartalmazzon nagybetűt és számot.</span>
			</div>
			<div class="p-field">
				<label>Új jelszó megerősítése</label>
				<input class="rt-input" type="password" name="password_confirmation" placeholder="••••••••">
			</div>
			<button type="submit" class="rt-btn rt-btn-primary rt-btn-large p-auth-submit">
				Jelszó módosítása
			</button>
		</div>

		<hr class="p-auth-sep">

		<div class="p-auth-switch">
			<a href="{{ route('login') }}">
				<i data-lucide="arrow-left" class="lucide-xs"></i> Vissza a belépéshez
			</a>
		</div>
	</form>

@endsection
