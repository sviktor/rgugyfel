{{--
	Password input with a show/hide toggle (eye icon inside the field).
	Hidden by default; the eye button flips the input type via Alpine.
	All extra attributes (name, id, placeholder, autocomplete, required,
	x-model, ...) pass through to the <input>. Styles: .p-pw-field /
	.p-pw-toggle in resources/css/auth.css.

	Usage:
		<x-pw-input name="password" placeholder="Min. 10 karakter" />
		<x-pw-input id="sec_pw" name="password" autocomplete="new-password" required x-model="pw" />
--}}
<div class="p-pw-field" x-data="{ show: false }">
	<input {{ $attributes->merge(['class' => 'rt-input']) }} :type="show ? 'text' : 'password'" type="password">
	<button type="button" class="p-pw-toggle" @click="show = !show" :aria-label="show ? 'Jelszó elrejtése' : 'Jelszó megjelenítése'" :aria-pressed="show">
		<i data-lucide="eye" class="lucide-sm" x-show="!show"></i>
		<i data-lucide="eye-off" class="lucide-sm" x-show="show" x-cloak></i>
	</button>
</div>
