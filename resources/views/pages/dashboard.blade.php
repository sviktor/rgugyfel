{{--
	Dashboard (Főoldal) - ported from portal-dashboard.jsx (Dashboard).
	Financial hero + bank-transfer modal + quick actions + contracts list +
	add-contract request. Real data from the linked customer (WebInvoices /
	WebContracts); an unlinked account shows only the welcome + add-contract
	state. Modals open/close with Alpine; the add-contract form files a pending
	cus_contract_requests row.
--}}
@extends('layouts.portal')

@section('title', 'Főoldal - Royal Telekom Ügyfélkapu')

@section('content')

	@php
		$fmt    = fn ($n) => \App\Support\PortalMockData::huf($n);
		$fdate  = fn ($d) => \App\Support\PortalMockData::date($d);
		$dago   = fn ($d) => \App\Support\PortalMockData::daysAgo($d);
		$duntil = fn ($d) => \App\Support\PortalMockData::daysUntil($d);

		$overdue  = array_values(array_filter($invoices, fn ($i) => $i['status'] === 'overdue'));
		$pending  = array_values(array_filter($invoices, fn ($i) => $i['status'] === 'pending'));
		$due      = array_merge($overdue, $pending);
		// The still-owed amount per invoice ('outstanding' = gross minus any
		// recorded partial payment); the mock fallback has no such key.
		$owed     = fn ($i) => $i['outstanding'] ?? $i['amount'];
		$totalDue = array_sum(array_map($owed, $due));
		$upcoming = $pending[0] ?? ($overdue[0] ?? null);

		$ref = $bank['ref'];
	@endphp

	<div class="p-page" x-data="{ bank: false, bankAmount: '', bankRef: '{{ $ref }}' }">

		@if ($linked)
		{{-- FINANCIAL HERO --}}
		<div class="p-hero-fin {{ count($overdue) ? 'has-overdue' : '' }}">
			<div class="p-hero-fin-side">
				<div class="eyebrow">Pénzügyi áttekintés</div>
				<div class="amount">
					{{ $fmt($totalDue) }}
					<span class="cnt">{{ count($due) }} db</span>
				</div>
				<div class="lede">
					@if (count($overdue) > 0)
						Egy <strong>lejárt</strong> számlát találtunk. Kérjük, mielőbb rendezze az alábbi banki utalási adatokkal.
					@elseif ($upcoming)
						A következő esedékes számla {{ $fdate($upcoming['due']) }}.
					@else
						Jelenleg nincs esedékes számlája.
					@endif
				</div>
				<div class="cta-row">
					<button type="button" class="rt-btn rt-btn-cream rt-btn-large"
					        @click="bank = true; bankAmount = '{{ $fmt($totalDue) }}'; bankRef = '{{ $ref }}'">
						<i data-lucide="credit-card" class="lucide-sm"></i> Kifizetem
					</button>
					<a href="{{ route('invoices') }}" class="rt-btn rt-btn-large p-btn-ghost-cream">
						Számlák megtekintése <i data-lucide="arrow-right" class="lucide-sm"></i>
					</a>
				</div>
			</div>
			<div class="p-hero-fin-list">
				<div class="head">
					<i data-lucide="receipt" class="lucide-sm"></i> Esedékes számlák
				</div>
				@forelse ($due as $inv)
					<div class="row {{ $inv['status'] }}">
						<div class="meta">
							<div class="id">{{ $inv['id'] }}</div>
							<div class="period">{{ $inv['period'] }}</div>
						</div>
						<div class="amt">
							{{ $fmt($owed($inv)) }}
							@if ($owed($inv) < $inv['amount'])
								<span class="p-partial">részben rendezve</span>
							@endif
						</div>
						<div class="state">
							@if ($inv['status'] === 'overdue')
								<span class="p-badge p-badge-danger">Lejárt {{ $dago($inv['due']) }} napja</span>
							@else
								<span class="p-badge p-badge-warn">Esedékes {{ $duntil($inv['due']) }} nap múlva</span>
							@endif
						</div>
						<button type="button" class="pay"
						        @click="bank = true; bankAmount = '{{ $fmt($owed($inv)) }}'; bankRef = '{{ $inv['id'] }}'">
							<i data-lucide="credit-card" class="lucide-xs"></i> Kifizetem
						</button>
					</div>
				@empty
					<div class="p-empty">
						<div class="ico"><i data-lucide="check-circle" class="lucide-xl"></i></div>
						<h4>Minden számla rendezve</h4>
						<p>Nincs esedékes tételünk.</p>
					</div>
				@endforelse
			</div>
		</div>
		@else
			{{-- Account not yet linked to a customer (pending approval): only the
			     contract-assignment card makes sense here, no customer data. --}}
			<div class="p-card p-pad">
				<div class="p-section-title">
					<div>
						<div class="eyebrow">Üdvözöljük az Ügyfélkapun</div>
						<h3>Rendelje a szerződését a fiókjához</h3>
					</div>
				</div>
				<p class="p-form-intro">Fiókja még nincs összekötve ügyfél-szerződéssel. Az alábbi űrlapon adja meg a szerződésszámát és születési dátumát - munkatársunk 1-2 munkanapon belül jóváhagyja. Ezt követően itt látja majd a számláit, szerződéseit és pénzügyi áttekintését.</p>
			</div>
		@endif

		{{-- ADD CONTRACT (Szerződés hozzárendelése) --}}
		@include('partials._add-contract')

		@if ($linked)
		{{-- BANK-TRANSFER MODAL --}}
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
						@php
							$bankRows = [
								['Kedvezményezett', $bank['name'], false],
								['IBAN', $bank['iban'], false],
								['Belföldi számlaszám', $bank['account'], false],
								['SWIFT / BIC', $bank['swift'], false],
								['Bank', $bank['bank'], false],
							];
						@endphp
						@foreach ($bankRows as [$label, $value, $dyn])
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

		{{-- CONTRACTS LIST --}}
		<div class="p-card p-pad">
			<div class="p-section-title">
				<div>
					<div class="eyebrow">Szerződéseim</div>
					<h3>{{ count($contracts) }} szerződés</h3>
				</div>
				<a href="{{ route('plans') }}" class="link">Részletek →</a>
			</div>
			<div class="p-contracts">
				@foreach ($contracts as $c)
					<div class="p-contract">
						<div class="ico" data-kind="{{ $c['kind'] }}">
							<i data-lucide="{{ $c['icon'] }}" class="lucide-md"></i>
						</div>
						<div class="body">
							<div class="row1">
								<strong>{{ $c['name'] }}</strong>
								@if ($c['status'] === 'active')
									<span class="p-badge p-badge-success">Aktív</span>
								@elseif ($c['status'] === 'pending')
									<span class="p-badge p-badge-warn">Jóváhagyásra vár</span>
								@else
									<span class="p-badge p-badge-neutral">Szüneteltetve</span>
								@endif
							</div>
							<div class="row2">
								<span>{{ $c['id'] }}</span>
								<span>·</span>
								<span>{{ $c['address'] }}</span>
							</div>
							<div class="row3">
								@foreach ($c['summary'] as $s)
									<span>{{ $s }}</span>
								@endforeach
							</div>
						</div>
						<div class="price">
							<div class="amt">{{ $fmt($c['monthly']) }}</div>
							<div class="lbl">/ hó</div>
						</div>
					</div>
				@endforeach
			</div>
		</div>
		@endif

	</div>

@endsection
