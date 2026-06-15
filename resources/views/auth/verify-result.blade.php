{{--
	Verify-result - the outcome of clicking the e-mail confirmation link.
	  $status = 'success' | 'expired' | 'invalid'
	Success shows a "Belépés a fiókba" button to the login screen; the failure
	states offer a re-send (back to the notice page).
--}}
@extends('layouts.auth')

@section('title', 'E-mail megerősítés - Royal Telekom Ügyfélkapu')
@section('side-eyebrow', 'E-MAIL MEGERŐSÍTÉS')
@section('side-headline', 'Köszönjük, hogy csatlakozott.')
@section('side-lede', 'Az e-mail cím megerősítése után fiókja aktív, és azonnal hozzáfér a teljes ügyfélkörnyezethez.')

@section('content')

	<div class="p-auth-form">
		@if ($status === 'success')
			<div class="p-auth-result-icon is-success">
				<i data-lucide="check-circle" class="lucide-xl"></i>
			</div>
			<h2>E-mail cím megerősítve</h2>
			<p class="sub p-auth-sub-sent">
				Köszönjük! E-mail címét sikeresen megerősítettük, fiókja aktív.
				Most már bejelentkezhet az Ügyfélkapuba.
			</p>
			<a href="{{ route('login') }}" class="rt-btn rt-btn-primary rt-btn-large p-auth-submit">
				Belépés a fiókba <i data-lucide="arrow-right" class="lucide-md"></i>
			</a>
		@else
			<div class="p-auth-result-icon is-error">
				<i data-lucide="alert-triangle" class="lucide-xl"></i>
			</div>
			<h2>A megerősítés nem sikerült</h2>
			<p class="sub p-auth-sub-sent">
				@if ($status === 'expired')
					A megerősítő link lejárt vagy érvénytelen. Kérjük, kérjen egy újat,
					és kattintson rá 24 órán belül.
				@else
					A megerősítő link érvénytelen. Lehet, hogy a címet nem teljesen másolta be,
					vagy a fiókot időközben már megerősítette.
				@endif
			</p>
			<a href="{{ route('register.verify.notice') }}" class="rt-btn rt-btn-secondary rt-btn-large p-auth-submit">
				<i data-lucide="send" class="lucide-md"></i> Új megerősítő e-mail kérése
			</a>
		@endif

		<hr class="p-auth-sep">

		<div class="p-auth-switch">
			<a href="{{ route('login') }}">
				<i data-lucide="arrow-left" class="lucide-xs"></i> Vissza a belépéshez
			</a>
		</div>
	</div>

@endsection
