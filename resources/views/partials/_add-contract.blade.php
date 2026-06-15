{{--
	"Szerződés hozzárendelése" card - shared by the dashboard and the Szerződéseim
	page. Files a PENDING contract request (ContractRequestController::store).

	The form posts via the portal's generic AJAX mechanism (`data-auth-form` ->
	resources/js/auth-forms.js): a validation error surfaces in the ptAlert
	lightbox in place, a success hands back a `redirect` so the page reloads and
	the `pt_alert` flash fires the confirmation modal (no inline modal needed).
	`novalidate` routes empty fields through the SAME server validation + modal.

	The account's COMPLETE pending requests ($pendingRequests, injected by the
	AppServiceProvider view composer - incomplete "Adatok megadására vár" rows are
	filtered out) are listed above the form so the customer sees what is already
	awaiting approval; the form stays below so further contracts can be filed (a
	portal account may hold unlimited contracts).

	Texts come from the portal CMS (home.add_contract.*, edited in rgadmin under
	WEBOLDALAK -> Ügyfélkapu -> Főoldal) with built-in fallbacks, so the card is
	correct even before the editor is first opened.
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

	@if ($pendingRequests->isNotEmpty())
		<div class="p-pending">
			<div class="p-pending-label">Függő kérelmek</div>
			<div class="p-contracts">
				@foreach ($pendingRequests as $req)
					<div class="p-contract">
						<div class="ico">
							<i data-lucide="file-text" class="lucide-md"></i>
						</div>
						<div class="body">
							<div class="row1">
								<strong>{{ $req->contract_number }}</strong>
								<span class="p-badge p-badge-warn">{{ $req->statusLabel() }}</span>
							</div>
							<div class="row2">
								<span>Beküldve: {{ optional($req->created_at)->format('Y. m. d.') ?: '-' }}</span>
							</div>
						</div>
					</div>
				@endforeach
			</div>
		</div>
		<div class="p-contract-form-label">Másik szerződés hozzárendelése</div>
	@endif

	<form class="p-contract-form" method="POST" action="{{ route('contract.request') }}" data-auth-form novalidate>
		@csrf
		<input type="hidden" name="return_to" value="{{ url()->current() }}">
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
