{{--
	Register screen - faithful port of portal-auth.jsx RegisterScreen.
	The 3-step wizard is a client-side Alpine component (registerWizard, in
	resources/js/auth-forms.js): step state + per-step validation shown in the
	ptAlert lightbox. The final step POSTs everything via AJAX (window.authSubmit)
	to RegisterController::store. Step 2 = birth date + (contract number OR full
	address); step 3 carries the reCAPTCHA.
--}}
@extends('layouts.auth')

@section('title', 'Regisztráció - Royal Telekom Ügyfélkapu')
@section('side-eyebrow', 'REGISZTRÁCIÓ')
@section('side-headline', 'Csatlakozzon az Ügyfélkapuhoz.')
@section('side-lede', 'Néhány adat megadásával Ön azonnal hozzáfér a teljes ügyfélkörnyezethez. Ha már Royal Telekom ügyfél, a szerződésszámát megtalálja a szerződésén vagy az utolsó számláján.')

@section('content')

	<form class="p-auth-form" method="POST" action="{{ route('register.submit') }}" novalidate
	      x-data="registerWizard()" @submit.prevent="onFormSubmit($event)">
		@csrf

		<h2>Regisztráció</h2>
		<p class="sub"><span x-text="step"></span>/3 lépés</p>

		<div class="p-auth-progress">
			<span :class="step >= 1 ? 'is-active' : ''"></span>
			<span :class="step >= 2 ? 'is-active' : ''"></span>
			<span :class="step >= 3 ? 'is-active' : ''"></span>
		</div>

		{{-- Step 1 - identity --}}
		<div class="p-auth-form-grid" x-show="step === 1" x-cloak>
			<div class="p-field-row">
				<div class="p-field">
					<label>Vezetéknév</label>
					<input class="rt-input" name="lastName" value="{{ old('lastName') }}" placeholder="Kis">
				</div>
				<div class="p-field">
					<label>Keresztnév</label>
					<input class="rt-input" name="firstName" value="{{ old('firstName') }}" placeholder="Éva">
				</div>
			</div>
			<div class="p-field">
				<label>E-mail cím</label>
				<input class="rt-input" type="email" name="email" value="{{ old('email') }}" placeholder="nev@example.hu">
			</div>
			<div class="p-field">
				<label>Telefonszám</label>
				<input class="rt-input" type="tel" name="phone" value="{{ old('phone') }}" placeholder="+36 30 123 4567">
			</div>
		</div>

		{{-- Step 2 - identification (OPTIONAL): contract number + birth date, or address --}}
		<div class="p-auth-form-grid" x-show="step === 2" x-cloak>
			<p class="hint">
				Az azonosítás <strong>nem kötelező</strong> - megadhatja most, vagy később az
				Ügyfélkapuban. A szerződéséhez akkor tudjuk kötni a fiókját, ha megadja a
				szerződésszámát és születési dátumát (vagy a teljes lakcímét).
			</p>
			<div class="p-field">
				<label>Szerződésszám <span class="p-label-opt">(nem kötelező)</span></label>
				<input class="rt-input" name="contract_number" value="{{ old('contract_number') }}" placeholder="SV00-00000">
				<span class="hint">A szerződésén és a számlái fejlécében találja, pl. „Szerződés: SV24-00170”.</span>
			</div>
			<div class="p-field">
				<label>Születési dátum <span class="p-label-opt">(nem kötelező)</span></label>
				<input class="rt-input" type="date" name="birth_date" value="{{ old('birth_date') }}">
				<span class="hint">A szerződő születési dátuma - a szerződésszámmal együtt az azonosításhoz.</span>
			</div>
			<div class="p-auth-or">
				<span></span> vagy a teljes lakcímmel <span></span>
			</div>
			<div class="p-field-row">
				<div class="p-field">
					<label>Irányítószám</label>
					<input class="rt-input" name="zip" value="{{ old('zip') }}" placeholder="1037">
				</div>
				<div class="p-field">
					<label>Település</label>
					<input class="rt-input" name="city" value="{{ old('city') }}" placeholder="Budapest">
				</div>
			</div>
			<div class="p-field">
				<label>Utca, házszám</label>
				<input class="rt-input" name="street" value="{{ old('street') }}" placeholder="Aranyhegyi út 14.">
			</div>
		</div>

		{{-- Step 3 - password + terms + reCAPTCHA --}}
		<div class="p-auth-form-grid" x-show="step === 3" x-cloak>
			<div class="p-field">
				<label>Jelszó</label>
				<input class="rt-input" type="password" name="password" placeholder="Min. 10 karakter">
				<span class="hint">Legalább 10 karakter, tartalmazzon nagybetűt és számot.</span>
			</div>
			<div class="p-field">
				<label>Jelszó megerősítése</label>
				<input class="rt-input" type="password" name="password_confirmation" placeholder="••••••••">
			</div>
			<label class="p-auth-accept">
				<input type="checkbox" name="accept" value="1">
				<span>Elfogadom az <a href="{{ route('terms') }}" target="_blank" rel="noopener">Általános Szerződési Feltételeket</a> és az <a href="{{ route('privacy') }}" target="_blank" rel="noopener">Adatvédelmi Tájékoztatót</a>.</span>
			</label>
			@if (\App\Support\Recaptcha::enabled())
				<div class="g-recaptcha" data-sitekey="{{ config('recaptcha.site_key') }}"></div>
			@endif
		</div>

		<div class="p-auth-actions">
			<button type="button" class="rt-btn rt-btn-secondary rt-btn-large" x-show="step > 1" x-cloak @click="prev()">
				<i data-lucide="arrow-left" class="lucide-md"></i> Vissza
			</button>
			<button type="submit" class="rt-btn rt-btn-primary rt-btn-large">
				<span x-text="step === 3 ? 'Regisztráció lezárása' : 'Tovább'"></span>
				<i data-lucide="arrow-right" class="lucide-md" x-show="step < 3" x-cloak></i>
			</button>
		</div>

		<hr class="p-auth-sep">

		<div class="p-auth-switch">
			Már van fiókja?
			<a href="{{ route('login') }}">Belépés <i data-lucide="arrow-right" class="lucide-xs"></i></a>
		</div>
	</form>

@endsection

@if (\App\Support\Recaptcha::enabled())
	@push('scripts')
		<script src="https://www.google.com/recaptcha/api.js" async defer></script>
	@endpush
@endif
