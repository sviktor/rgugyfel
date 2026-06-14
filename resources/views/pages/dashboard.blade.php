{{--
	Dashboard (Főoldal) - ported from portal-dashboard.jsx (Dashboard).
	Financial hero + bank-transfer modal + active ticket + quick actions +
	contracts list + add-contract request. Mock data via PortalMockData.
	Modals open/close with Alpine; submitting the add-contract form shows the
	confirmation state (no backend yet - that lands in the programming phase).
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
		$totalDue = array_sum(array_column($due, 'amount'));
		$upcoming = $pending[0] ?? ($overdue[0] ?? null);
		$ticket   = collect($tickets)->firstWhere('status', 'open');

		$ref = $bank['ref'];
	@endphp

	<div class="p-page" x-data="{ bank: false, bankAmount: '', bankRef: '{{ $ref }}', addConfirm: false }">

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
						<div class="amt">{{ $fmt($inv['amount']) }}</div>
						<div class="state">
							@if ($inv['status'] === 'overdue')
								<span class="p-badge p-badge-danger">Lejárt {{ $dago($inv['due']) }} napja</span>
							@else
								<span class="p-badge p-badge-warn">Esedékes {{ $duntil($inv['due']) }} nap múlva</span>
							@endif
						</div>
						<button type="button" class="pay"
						        @click="bank = true; bankAmount = '{{ $fmt($inv['amount']) }}'; bankRef = '{{ $inv['id'] }}'">
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

		{{-- BANK-TRANSFER MODAL --}}
		<div class="p-modal-bg" x-show="bank" x-cloak @click="bank = false" x-transition.opacity>
			<div class="p-modal" @click.stop>
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
								<button type="button" class="copy" @click="navigator.clipboard && navigator.clipboard.writeText('{{ $value }}')" aria-label="Másolás">
									<i data-lucide="copy" class="lucide-sm"></i>
								</button>
							</div>
						@endforeach
						<div class="row">
							<span class="lbl">Közlemény</span>
							<span class="val" x-text="bankRef"></span>
							<button type="button" class="copy" @click="navigator.clipboard && navigator.clipboard.writeText(bankRef)" aria-label="Másolás">
								<i data-lucide="copy" class="lucide-sm"></i>
							</button>
						</div>
						<div class="row">
							<span class="lbl">Átutalandó összeg</span>
							<span class="val" x-text="bankAmount"></span>
							<button type="button" class="copy" @click="navigator.clipboard && navigator.clipboard.writeText(bankAmount)" aria-label="Másolás">
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

		{{-- SECONDARY GRID: active ticket + quick actions --}}
		<div class="p-grid-2">

			<div class="p-card">
				<div class="p-dash-tickethead">
					<div class="p-section-title">
						<div>
							<div class="eyebrow">Hibabejelentés</div>
							<h3>Aktuális ügyei</h3>
						</div>
						<a href="{{ route('tickets') }}" class="link">Összes →</a>
					</div>
				</div>
				@if ($ticket)
					<div class="p-dash-ticketbody">
						<div class="p-ticket-mini">
							<div class="num">#{{ $ticket['id'] }}</div>
							<div class="title">{{ $ticket['subject'] }}</div>
							<div class="meta">
								@if ($ticket['priority'] === 'high')
									<span class="p-badge p-badge-danger">Magas prioritás</span>
								@else
									<span class="p-badge p-badge-warn">Normál</span>
								@endif
								<span>Megnyitva {{ $dago($ticket['opened']) }} napja</span>
								<span>·</span>
								<span>{{ count($ticket['messages']) }} üzenet</span>
							</div>
							<p>{{ $ticket['lastMessage'] }}</p>
							<a href="{{ route('tickets') }}" class="rt-btn rt-btn-secondary">
								Részletek megnyitása <i data-lucide="arrow-right" class="lucide-xs"></i>
							</a>
						</div>
					</div>
				@else
					<div class="p-dash-ticketempty">
						<div class="p-empty">
							<div class="ico"><i data-lucide="check-circle" class="lucide-xl"></i></div>
							<h4>Nincs nyitott hibajegy</h4>
							<p>Ha problémát észlel, jelentse be itt.</p>
						</div>
					</div>
				@endif
			</div>

			<div class="p-card p-pad">
				<div class="p-section-title">
					<div>
						<div class="eyebrow">Gyors műveletek</div>
						<h3>Mit szeretne tenni?</h3>
					</div>
				</div>
				<div class="p-quick">
					<a href="{{ route('tickets') }}"><i data-lucide="alert-circle" class="lucide-md"></i> <span>Új hibabejelentés</span><i data-lucide="arrow-right" class="lucide-xs arr"></i></a>
					<a href="{{ route('usage') }}"><i data-lucide="activity" class="lucide-md"></i> <span>Sebességmérés</span><i data-lucide="arrow-right" class="lucide-xs arr"></i></a>
					<a href="{{ route('docs') }}"><i data-lucide="file-text" class="lucide-md"></i> <span>Szerződés letöltése</span><i data-lucide="arrow-right" class="lucide-xs arr"></i></a>
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

		{{-- ADD CONTRACT --}}
		<div class="p-card p-pad">
			<div class="p-section-title">
				<div>
					<div class="eyebrow">Új szerződés</div>
					<h3>Szerződés hozzárendelése</h3>
				</div>
			</div>
			<p class="p-contract-intro">
				Egy fiókhoz korlátlan számú szerződés tartozhat - például egy másik ingatlan vagy hozzátartozó szolgáltatása.
				Adja meg a szerződésszámot és a szerződő születési dátumát. <strong>A hozzárendelés egy kérelem,
				amelyet munkatársaink jóváhagynak.</strong>
			</p>
			<form class="p-contract-form" @submit.prevent="addConfirm = true">
				<div class="p-field">
					<label for="ac_num">Szerződésszám</label>
					<input id="ac_num" class="rt-input" required placeholder="RT-2024-0382">
				</div>
				<div class="p-field">
					<label for="ac_dob">Születési dátum</label>
					<input id="ac_dob" class="rt-input" type="date" required>
				</div>
				<button type="submit" class="rt-btn rt-btn-primary">
					<i data-lucide="send" class="lucide-sm"></i> Kérelem beküldése
				</button>
			</form>
		</div>

		{{-- ADD-CONTRACT CONFIRMATION --}}
		<div class="p-modal-bg" x-show="addConfirm" x-cloak @click="addConfirm = false" x-transition.opacity>
			<div class="p-modal p-modal--sm" @click.stop>
				<div class="p-switch-done p-addconfirm">
					<div class="check p-check-gold"><i data-lucide="send" class="lucide-2xl"></i></div>
					<h4>Kérelmét rögzítettük.</h4>
					<p>
						A szerződés-hozzárendelési kérelmét továbbítottuk munkatársaink részére.
						A jóváhagyás <strong>1-2 munkanapon belül</strong> megtörténik, az eredményről e-mailben értesítjük.
						Addig a szerződés <em>„Jóváhagyásra vár"</em> státuszban jelenik meg a Szerződéseim oldalon.
					</p>
					<div class="summary">
						<div><span>Beküldve</span><strong>{{ $fdate('2026-05-05') }}</strong></div>
						<div><span>Státusz</span><strong>Jóváhagyásra vár</strong></div>
					</div>
					<button type="button" class="rt-btn rt-btn-primary rt-btn-large" @click="addConfirm = false">Bezárás</button>
				</div>
			</div>
		</div>

	</div>

@endsection
