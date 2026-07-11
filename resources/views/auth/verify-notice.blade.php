{{--
	Verify-notice - the "we sent you a confirmation e-mail" landing page shown
	right after registration (and when an unverified user tries to log in).
	Reuses the forgot "sent" pattern (.p-auth-sent-icon + mail-check). The resend
	form re-issues the verification link (throttled route, no e-mail enumeration).
--}}
@extends('layouts.auth')

@section('title', 'E-mail megerősítés - Royal Telekom Ügyfélkapu')
@section('side-eyebrow', 'E-MAIL MEGERŐSÍTÉS')
@section('side-headline', 'Már csak egy lépés van hátra.')
@section('side-lede', 'A fiók aktiválásához erősítse meg e-mail címét a kiküldött levélben található gombbal. Ezután azonnal beléphet az Ügyfélkapuba.')

@section('content')

	<div class="p-auth-form">
		<div class="p-auth-sent-icon">
			<i data-lucide="mail-check" class="lucide-xl"></i>
		</div>
		<h2>Erősítse meg e-mail címét</h2>
		<p class="sub p-auth-sub-sent">
			A megerősítő linket elküldtük a megadott címre:
			@if ($email !== '')
				<strong class="p-auth-email">{{ $email }}</strong>.
			@else
				a regisztrációkor megadott e-mail címre.
			@endif
			Kérjük, nézze meg a beérkező leveleit (és a Spam mappát is).
		</p>

		<div class="p-auth-sent-note">
			Nem érkezett meg? Ellenőrizze a Spam mappát, vagy küldje el újra:
			<form method="POST" action="{{ route('verification.resend') }}" class="p-auth-resend" data-auth-form>
				@csrf
				@if ($email !== '')
					<input type="hidden" name="email" value="{{ $email }}">
				@else
					{{-- The session no longer knows the address (e.g. arriving from an
					     expired link the next day): ask for it instead of posting a
					     guaranteed-invalid empty hidden value (audit E3-M3). --}}
					<input type="email" name="email" class="rt-input" required
						placeholder="nev@example.hu" aria-label="E-mail cím">
				@endif
				<button type="submit" class="rt-btn rt-btn-secondary">
					<i data-lucide="send" class="lucide-sm"></i> Megerősítő e-mail újraküldése
				</button>
			</form>
		</div>

		<hr class="p-auth-sep">

		<div class="p-auth-switch">
			<a href="{{ route('login') }}">
				<i data-lucide="arrow-left" class="lucide-xs"></i> Vissza a belépéshez
			</a>
		</div>
	</div>

@endsection
