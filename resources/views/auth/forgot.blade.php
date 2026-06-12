{{--
	Password reset request screen - ported from portal-auth.jsx ForgotScreen.
	Post-submit "sent" state via session flag.
--}}
@extends('layouts.auth')

@section('title', 'Elfelejtett jelszó - Royal Telekom Ügyfélkapu')
@section('side-eyebrow', 'JELSZÓ-HELYREÁLLÍTÁS')
@section('side-headline', 'Visszaállítjuk önnek a hozzáférést.')
@section('side-lede', 'Adja meg e-mail címét, és küldünk egy biztonságos linket, amellyel új jelszót állíthat be. A link 30 percig érvényes.')

@section('content')

	<form class="p-auth-form" method="POST" action="{{ route('forgot.submit') }}">
		@csrf

		@if (session('forgot_sent'))
			<div class="p-auth-sent-icon">
				<i data-lucide="mail-check" class="lucide-xl"></i>
			</div>
			<h2>E-mail elküldve</h2>
			<p class="sub" style="margin-bottom:22px;">
				A helyreállító linket elküldtük a megadott címre:
				<strong style="color:var(--p-fg-1);">{{ session('forgot_email') }}</strong>.
				Kérjük, nézze meg a beérkező leveleit (és a Spam mappát is).
			</p>
			<div class="p-auth-sent-note">
				Nem érkezett meg? Ellenőrizze a Spam mappát, vagy
				<a href="{{ route('forgot') }}" style="color:var(--p-fg-1);font-weight:600;">küldje újra</a>.
			</div>
		@else
			<h2>Elfelejtett jelszó</h2>
			<p class="sub">Megküldjük az új jelszó beállításához szükséges linket.</p>
			<div class="p-field" style="margin-bottom:18px;">
				<label>E-mail cím</label>
				<input class="rt-input" type="email" name="email" required
				       value="{{ old('email') }}" placeholder="kis.eva@example.hu" autofocus>
			</div>
			<button type="submit" class="rt-btn rt-btn-primary rt-btn-large p-auth-submit">
				Helyreállító link küldése
			</button>
		@endif

		<hr class="p-auth-sep">

		<div class="p-auth-switch">
			<a href="{{ route('login') }}">
				<i data-lucide="arrow-left" class="lucide-xs"></i> Vissza a belépéshez
			</a>
		</div>
	</form>

@endsection
