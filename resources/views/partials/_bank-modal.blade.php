{{--
	Bank-transfer details modal (BankDetailsCard) - included by the invoices
	page (the dashboard inlines an equivalent copy). Expects:
	- $bank  array  bank-transfer details. CMS-sourced: PortalController builds
	  it from web_sections global.bank.* (edited in rgadmin under WEBOLDALAK ->
	  Ügyfélkapu -> Beállítások); the account fields stay empty until filled.
	Alpine state on an ancestor: bank (bool), bankAmount (string), bankRef (string).
--}}
<div class="p-modal-bg" x-show="bank" x-cloak @click="bank = false" x-transition.opacity>
	<div class="p-modal p-modal-bank" @click.stop>
		<div class="p-bank">
			<div class="p-bank-head">
				<div>
					<div class="eyebrow">Banki utalás adatai</div>
					<h3>Utalja át az alábbi adatokkal</h3>
				</div>
				<button type="button" class="rt-btn rt-btn-ghost" @click="bank = false" aria-label="Bezárás">
					<i data-lucide="x" class="lucide-md"></i>
				</button>
			</div>
			<p class="p-bank-lede">
				A teljesítést a beérkezést követő 1-2 banki napon belül látjuk a rendszerünkben.
				Kérjük, a <strong>Közlemény</strong> mezőbe pontosan írja be a számla azonosítóját.
			</p>
			<div class="p-bank-rows">
				@foreach ([['Kedvezményezett', $bank['name']], ['IBAN', $bank['iban']], ['Belföldi számlaszám', $bank['account']], ['SWIFT / BIC', $bank['swift']], ['Bank', $bank['bank']]] as [$label, $value])
					<div class="row">
						<span class="lbl">{{ $label }}</span>
						<span class="val">{{ $value }}</span>
						<button type="button" class="copy" x-data="{ copied: false }" :class="{ 'is-copied': copied }"
						        @click="ptCopy(@js($value)).then(ok => { if (ok) { copied = true; setTimeout(() => copied = false, 1400); } })"
						        :aria-label="copied ? 'Másolva' : 'Másolás'">
							<i data-lucide="copy" class="lucide-sm"></i>
						</button>
					</div>
				@endforeach
				<div class="row">
					<span class="lbl">Közlemény</span>
					<span class="val" x-text="bankRef"></span>
					<button type="button" class="copy" x-data="{ copied: false }" :class="{ 'is-copied': copied }"
					        @click="ptCopy(bankRef).then(ok => { if (ok) { copied = true; setTimeout(() => copied = false, 1400); } })"
					        :aria-label="copied ? 'Másolva' : 'Másolás'">
						<i data-lucide="copy" class="lucide-sm"></i>
					</button>
				</div>
				<div class="row">
					<span class="lbl">Átutalandó összeg</span>
					<span class="val" x-text="bankAmount"></span>
					<button type="button" class="copy" x-data="{ copied: false }" :class="{ 'is-copied': copied }"
					        @click="ptCopy(bankAmount).then(ok => { if (ok) { copied = true; setTimeout(() => copied = false, 1400); } })"
					        :aria-label="copied ? 'Másolva' : 'Másolás'">
						<i data-lucide="copy" class="lucide-sm"></i>
					</button>
				</div>
			</div>
			<div class="p-bank-foot">
				<i data-lucide="info" class="lucide-sm"></i>
				<span>A teljesítési határidő: a számlán feltüntetett esedékességi nap. Késedelem esetén a Ptk. szerinti késedelmi kamatot számoljuk fel.</span>
			</div>
		</div>
	</div>
</div>
