{{--
	Register screen - ported from portal-auth.jsx RegisterScreen.
	The 3-step wizard is rendered server-side with a `?step=` query param
	(simpler than client-side state; the 3 short steps don't justify Alpine).
--}}
@extends('layouts.auth')

@section('title', 'Regisztráció - Royal Telekom Ügyfélkapu')
@section('side-eyebrow', 'REGISZTRÁCIÓ')
@section('side-headline', 'Csatlakozzon az Ügyfélkapuhoz.')
@section('side-lede', 'Néhány adat megadásával Ön azonnal hozzáfér a teljes ügyfélkörnyezethez. Ha már Royal Telekom ügyfél, a szerződésszámát megtalálja a szerződésén vagy az utolsó számláján.')

@section('content')

	@php
		$step = (int) request()->query('step', 1);
		if ($step < 1 || $step > 3) $step = 1;
	@endphp

	<form class="p-auth-form" method="{{ $step === 3 ? 'POST' : 'GET' }}" action="{{ $step === 3 ? route('register.submit') : route('register') }}">
		@if ($step === 3)
			@csrf
		@endif

		<h2>Regisztráció</h2>
		<p class="sub">{{ $step }}/3 lépés</p>

		<div class="p-auth-progress">
			<span class="{{ $step >= 1 ? 'is-active' : '' }}"></span>
			<span class="{{ $step >= 2 ? 'is-active' : '' }}"></span>
			<span class="{{ $step >= 3 ? 'is-active' : '' }}"></span>
		</div>

		@if ($step === 1)
			<div class="p-auth-form-grid">
				<div class="p-field-row">
					<div class="p-field">
						<label>Vezetéknév</label>
						<input class="rt-input" name="lastName" required placeholder="Kis">
					</div>
					<div class="p-field">
						<label>Keresztnév</label>
						<input class="rt-input" name="firstName" required placeholder="Éva">
					</div>
				</div>
				<div class="p-field">
					<label>E-mail cím</label>
					<input class="rt-input" type="email" name="email" required placeholder="nev@example.hu">
				</div>
				<div class="p-field">
					<label>Telefonszám</label>
					<input class="rt-input" type="tel" name="phone" required placeholder="+36 30 123 4567">
				</div>
			</div>
			<input type="hidden" name="step" value="2">
		@endif

		@if ($step === 2)
			<div class="p-auth-form-grid">
				<div class="p-field">
					<label>Szerződésszám <span class="p-label-opt">(meglévő ügyfél esetén)</span></label>
					<input class="rt-input" name="customerId" placeholder="RT-INT-2024-0382">
					<span class="hint">A szerződésén és a számlái fejlécében találja, pl. „Szerződés: RT-INT-2024-0382”.</span>
				</div>
				<div style="display:flex;align-items:center;gap:10px;color:var(--p-fg-3);font-size:12px;">
					<span style="flex:1;height:1px;background:var(--p-border);"></span>
					vagy
					<span style="flex:1;height:1px;background:var(--p-border);"></span>
				</div>
				<div class="p-field-row">
					<div class="p-field">
						<label>Irányítószám</label>
						<input class="rt-input" name="zip" placeholder="1037">
					</div>
					<div class="p-field">
						<label>Település</label>
						<input class="rt-input" name="city" placeholder="Budapest">
					</div>
				</div>
				<div class="p-field">
					<label>Utca, házszám</label>
					<input class="rt-input" name="street" placeholder="Aranyhegyi út 14.">
				</div>
			</div>
			<input type="hidden" name="step" value="3">
		@endif

		@if ($step === 3)
			<div class="p-auth-form-grid">
				<div class="p-field">
					<label>Jelszó</label>
					<input class="rt-input" type="password" name="password" required placeholder="Min. 10 karakter">
					<span class="hint">Legalább 10 karakter, tartalmazzon nagybetűt és számot.</span>
				</div>
				<div class="p-field">
					<label>Jelszó megerősítése</label>
					<input class="rt-input" type="password" name="password_confirmation" required placeholder="••••••••">
				</div>
				<label style="display:flex;align-items:flex-start;gap:10px;font-size:13px;color:var(--p-fg-2);margin-top:6px;">
					<input type="checkbox" name="accept" value="1" required style="accent-color:var(--rt-navy-800);margin-top:2px;">
					<span>Elfogadom az <a href="#" style="color:var(--p-fg-1);font-weight:600;">Általános Szerződési Feltételeket</a> és az <a href="#" style="color:var(--p-fg-1);font-weight:600;">Adatvédelmi Tájékoztatót</a>.</span>
				</label>
			</div>
		@endif

		<div class="p-auth-actions">
			@if ($step > 1)
				<a href="{{ route('register', ['step' => $step - 1]) }}" class="rt-btn rt-btn-secondary rt-btn-large">
					<i data-lucide="arrow-left" class="lucide-md"></i> Vissza
				</a>
			@endif
			<button type="submit" class="rt-btn rt-btn-primary rt-btn-large">
				{{ $step === 3 ? 'Regisztráció lezárása' : 'Tovább' }}
				@if ($step < 3)
					<i data-lucide="arrow-right" class="lucide-md"></i>
				@endif
			</button>
		</div>

		<hr class="p-auth-sep">

		<div class="p-auth-switch">
			Már van fiókja?
			<a href="{{ route('login') }}">Belépés <i data-lucide="arrow-right" class="lucide-xs"></i></a>
		</div>
	</form>

@endsection
