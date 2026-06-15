{{--
	"Szerződés hozzárendelése" card - shared by the dashboard and the Szerződéseim
	page. Files a PENDING contract request (ContractRequestController::store);
	the confirmation is shown via the pt_alert flash after the redirect (no
	inline modal). Texts come from the portal CMS (home.add_contract.*, edited in
	rgadmin under WEBOLDALAK -> Ügyfélkapu -> Főoldal) with built-in fallbacks, so
	the card is correct even before the editor is first opened.
--}}
<div class="p-card p-pad">
	<div class="p-section-title">
		<div>
			<div class="eyebrow">{{ $cms->get('home.add_contract.eyebrow') ?: 'Új szerződés' }}</div>
			<h3>{{ $cms->get('home.add_contract.heading') ?: 'Szerződés hozzárendelése' }}</h3>
		</div>
	</div>
	<p class="p-contract-intro">
		@if ($cms->get('home.add_contract.intro') !== '')
			{!! $cms->text('home.add_contract.intro') !!}
		@else
			Egy fiókhoz korlátlan számú szerződés tartozhat - például egy másik ingatlan vagy hozzátartozó szolgáltatása.
			Adja meg a szerződésszámot és a szerződő születési dátumát. <strong>A hozzárendelés egy kérelem,
			amelyet munkatársaink jóváhagynak.</strong>
		@endif
	</p>
	<form class="p-contract-form" method="POST" action="{{ route('contract.request') }}">
		@csrf
		<div class="p-field">
			<label for="ac_num">Szerződésszám</label>
			<input id="ac_num" name="contract_number" class="rt-input" required placeholder="SV00-00000">
		</div>
		<div class="p-field">
			<label for="ac_dob">Születési dátum</label>
			<input id="ac_dob" name="birth_date" class="rt-input" type="date" required>
		</div>
		<button type="submit" class="rt-btn rt-btn-primary">
			<i data-lucide="send" class="lucide-sm"></i> Kérelem beküldése
		</button>
	</form>
</div>
