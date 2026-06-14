{{--
	Invoices (Számláim) - ported from portal-dashboard.jsx (InvoicesPage +
	InvoicePreview). Filter tabs + table + bank-transfer modal + a PDF-like
	invoice preview (one modal per invoice). Filtering + modals via Alpine;
	mock data via PortalMockData.
--}}
@extends('layouts.portal')

@section('title', 'Számláim - Royal Telekom Ügyfélkapu')

@section('content')

	@php
		$fmt   = fn ($n) => \App\Support\PortalMockData::huf($n);
		$fdate = fn ($d) => \App\Support\PortalMockData::date($d);

		$sums = [
			'all'    => count($invoices),
			'unpaid' => count(array_filter($invoices, fn ($i) => in_array($i['status'], ['overdue', 'pending'], true))),
			'paid'   => count(array_filter($invoices, fn ($i) => $i['status'] === 'paid')),
		];
	@endphp

	<div class="p-page" x-data="{ filter: 'all', openInvoice: null, bank: false, bankAmount: '', bankRef: '{{ $bank['ref'] }}' }">

		{{-- FILTER TABS --}}
		<div class="p-tabs">
			<button type="button" :class="{ on: filter === 'all' }" @click="filter = 'all'">Összes <span class="cnt">{{ $sums['all'] }}</span></button>
			<button type="button" :class="{ on: filter === 'unpaid' }" @click="filter = 'unpaid'">Esedékes <span class="cnt">{{ $sums['unpaid'] }}</span></button>
			<button type="button" :class="{ on: filter === 'paid' }" @click="filter = 'paid'">Kifizetett <span class="cnt">{{ $sums['paid'] }}</span></button>
		</div>

		{{-- TABLE --}}
		<div class="p-card">
			<table class="p-table">
				<thead>
					<tr>
						<th>Számla</th>
						<th>Időszak</th>
						<th>Esedékesség</th>
						<th class="ta-right">Összeg</th>
						<th>Állapot</th>
						<th></th>
					</tr>
				</thead>
				<tbody>
					@foreach ($invoices as $inv)
						@php $unpaid = in_array($inv['status'], ['overdue', 'pending'], true); @endphp
						<tr x-show="{{ $unpaid ? "filter !== 'paid'" : "filter !== 'unpaid'" }}">
							<td>
								<button type="button" class="link-btn" @click="openInvoice = '{{ $inv['id'] }}'">{{ $inv['id'] }}</button>
								<div class="sub">{{ $inv['service'] }}</div>
							</td>
							<td>{{ $inv['period'] }}</td>
							<td>{{ $fdate($inv['due']) }}</td>
							<td class="amt">{{ $fmt($inv['amount']) }}</td>
							<td>
								@if ($inv['status'] === 'paid')
									<span class="p-badge p-badge-success">Kifizetve</span>
								@elseif ($inv['status'] === 'pending')
									<span class="p-badge p-badge-warn">Esedékes</span>
								@else
									<span class="p-badge p-badge-danger">Lejárt</span>
								@endif
							</td>
							<td>
								<div class="actions">
									@if ($unpaid)
										<button type="button" class="rt-btn rt-btn-primary p-btn-sm"
										        @click="bank = true; bankAmount = '{{ $fmt($inv['amount']) }}'; bankRef = '{{ $inv['id'] }}'">
											<i data-lucide="credit-card" class="lucide-xs"></i> Kifizetem
										</button>
									@endif
									<button type="button" class="rt-btn rt-btn-ghost p-btn-sm" @click="openInvoice = '{{ $inv['id'] }}'" aria-label="Megnyitás">
										<i data-lucide="eye" class="lucide-sm"></i>
									</button>
									<button type="button" class="rt-btn rt-btn-ghost p-btn-sm" aria-label="PDF letöltés">
										<i data-lucide="download" class="lucide-sm"></i>
									</button>
								</div>
							</td>
						</tr>
					@endforeach
				</tbody>
			</table>
		</div>

		{{-- BANK-TRANSFER MODAL --}}
		@include('partials._bank-modal')

		{{-- INVOICE PREVIEW MODALS (one per invoice) --}}
		@foreach ($invoices as $inv)
			@php
				$unpaid = in_array($inv['status'], ['overdue', 'pending'], true);
				$lines  = ! empty($inv['lines']) ? $inv['lines'] : [['name' => $inv['service'], 'note' => null, 'qty' => '1 hó', 'unit' => $inv['amount'], 'total' => $inv['amount']]];
				$net    = (int) round($inv['amount'] / 1.27);
				$vat    = $inv['amount'] - $net;
			@endphp
			<div class="p-modal-bg" x-show="openInvoice === '{{ $inv['id'] }}'" x-cloak @click="openInvoice = null" x-transition.opacity>
				<div class="p-modal p-invoice-doc" @click.stop>
					<div class="p-invoice-toolbar">
						<button type="button" class="rt-btn rt-btn-ghost" @click="openInvoice = null"><i data-lucide="x" class="lucide-md"></i></button>
						<div class="p-spacer"></div>
						<button type="button" class="rt-btn rt-btn-secondary"><i data-lucide="download" class="lucide-xs"></i> PDF letöltés</button>
						<button type="button" class="rt-btn rt-btn-secondary"><i data-lucide="printer" class="lucide-xs"></i> Nyomtatás</button>
						@if ($unpaid)
							<button type="button" class="rt-btn rt-btn-primary"
							        @click="bank = true; bankAmount = '{{ $fmt($inv['amount']) }}'; bankRef = '{{ $inv['id'] }}'; openInvoice = null">
								<i data-lucide="credit-card" class="lucide-xs"></i> Kifizetem
							</button>
						@endif
					</div>
					<div class="p-invoice-paper">
						<div class="head">
							<div>
								<img class="p-invoice-logo" src="{{ asset('assets/royaltelekom-logo.svg') }}" alt="">
								<div class="addr">
									<strong>Royal Telekom Kft.</strong><br>
									1145 Budapest, Aranyhegyi út 14.<br>
									Adószám: 12345678-2-42<br>
									Cégjegyzékszám: 01-09-123456
								</div>
							</div>
							<div class="meta">
								<div class="kind">Számla / Invoice</div>
								<div class="num">{{ $inv['id'] }}</div>
								<div class="row"><span>Kiállítás</span><span>{{ $fdate($inv['issued'] ?? $inv['due']) }}</span></div>
								<div class="row"><span>Esedékesség</span><span>{{ $fdate($inv['due']) }}</span></div>
								<div class="row"><span>Időszak</span><span>{{ $inv['period'] }}</span></div>
								<div class="row"><span>Állapot</span>
									<span>
										@if ($inv['status'] === 'paid')
											<span class="p-badge p-badge-success">Kifizetve</span>
										@elseif ($inv['status'] === 'pending')
											<span class="p-badge p-badge-warn">Esedékes</span>
										@else
											<span class="p-badge p-badge-danger">Lejárt</span>
										@endif
									</span>
								</div>
							</div>
						</div>

						<div class="customer">
							<div class="lbl">Vevő</div>
							<strong>{{ $user['name'] }}</strong>
							<div>{{ $user['address'] }}</div>
							<div>Ügyfél-azonosító: #{{ $user['customerId'] }}</div>
						</div>

						<table class="lines">
							<thead>
								<tr><th>Megnevezés</th><th>Mennyiség</th><th class="ta-right">Egységár</th><th class="ta-right">Összesen</th></tr>
							</thead>
							<tbody>
								@foreach ($lines as $l)
									<tr>
										<td>
											<strong>{{ $l['name'] }}</strong>
											@if (! empty($l['note']))
												<div class="sub">{{ $l['note'] }}</div>
											@endif
										</td>
										<td>{{ $l['qty'] }}</td>
										<td class="ta-right">{{ $fmt($l['unit']) }}</td>
										<td class="ta-right"><strong>{{ $fmt($l['total']) }}</strong></td>
									</tr>
								@endforeach
							</tbody>
						</table>

						<div class="totals">
							<div class="row"><span>Nettó</span><span>{{ $fmt($net) }}</span></div>
							<div class="row"><span>ÁFA (27%)</span><span>{{ $fmt($vat) }}</span></div>
							<div class="row big"><span>Bruttó összesen</span><span>{{ $fmt($inv['amount']) }}</span></div>
						</div>

						<div class="foot">
							<strong>Fizetési mód:</strong> banki átutalás · Royal Telekom Kft. · IBAN {{ $bank['iban'] }} · Közlemény: <strong>{{ $inv['id'] }}</strong>
						</div>
					</div>
				</div>
			</div>
		@endforeach

	</div>

@endsection
