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
		// Still-owed amount ('outstanding' = gross minus any partial payment);
		// the mock fallback has no such key, so it falls back to the gross.
		$owed  = fn ($i) => $i['outstanding'] ?? $i['amount'];

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
							<td class="amt">
								{{ $fmt($inv['amount']) }}
								@if ($owed($inv) > 0 && $owed($inv) < $inv['amount'])
									<div class="p-partial">hátralék: {{ $fmt($owed($inv)) }}</div>
								@endif
							</td>
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
										        @click="bank = true; bankAmount = '{{ $fmt($owed($inv)) }}'; bankRef = '{{ $inv['id'] }}'">
											<i data-lucide="credit-card" class="lucide-xs"></i> Kifizetem
										</button>
									@endif
									<button type="button" class="rt-btn rt-btn-ghost p-btn-sm" @click="openInvoice = '{{ $inv['id'] }}'" aria-label="Megnyitás">
										<i data-lucide="eye" class="lucide-sm"></i>
									</button>
									@if (! empty($inv['downloadUrl']))
										<a href="{{ $inv['downloadUrl'] }}" class="rt-btn rt-btn-ghost p-btn-sm" aria-label="PDF letöltés">
											<i data-lucide="download" class="lucide-sm"></i>
										</a>
									@else
										<button type="button" class="rt-btn rt-btn-ghost p-btn-sm" aria-label="PDF letöltés">
											<i data-lucide="download" class="lucide-sm"></i>
										</button>
									@endif
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
				$unpaid     = in_array($inv['status'], ['overdue', 'pending'], true);
				$seller     = $inv['seller'] ?? null;
				$buyer      = $inv['buyer'] ?? ['name' => '', 'address' => '', 'tax' => '', 'number' => ''];
				$sellerName = $seller['name'] ?? ($cms->get('global.company.name') ?: 'Royal Telekom Kft.');
				$sellerAddr = $seller['address'] ?? ($cms->get('global.company.address') ?: '');
				$sellerTax  = $seller['tax'] ?? ($cms->get('global.company.tax_number') ?: '');
			@endphp
			<div class="p-modal-bg" x-show="openInvoice === '{{ $inv['id'] }}'" x-cloak @click="openInvoice = null" x-transition.opacity>
				<div class="p-modal p-invoice-doc" @click.stop>
					<div class="p-invoice-toolbar">
						<button type="button" class="rt-btn rt-btn-ghost" @click="openInvoice = null"><i data-lucide="x" class="lucide-md"></i></button>
						<div class="p-spacer"></div>
						@if (! empty($inv['downloadUrl']))
							<a href="{{ $inv['downloadUrl'] }}" class="rt-btn rt-btn-secondary"><i data-lucide="download" class="lucide-xs"></i> PDF letöltés</a>
						@else
							<button type="button" class="rt-btn rt-btn-secondary"><i data-lucide="download" class="lucide-xs"></i> PDF letöltés</button>
						@endif
						<button type="button" class="rt-btn rt-btn-secondary"><i data-lucide="printer" class="lucide-xs"></i> Nyomtatás</button>
						@if ($unpaid)
							<button type="button" class="rt-btn rt-btn-primary"
							        @click="bank = true; bankAmount = '{{ $fmt($owed($inv)) }}'; bankRef = '{{ $inv['id'] }}'; openInvoice = null">
								<i data-lucide="credit-card" class="lucide-xs"></i> Kifizetem
							</button>
						@endif
					</div>
					<div class="p-invoice-paper">
						<div class="head">
							<div>
								<img class="p-invoice-logo" src="{{ asset('assets/royaltelekom-logo.svg') }}" alt="">
								<div class="addr">
									<strong>{{ $sellerName }}</strong><br>
									{{ $sellerAddr }}@if ($sellerTax)<br>Adószám: {{ $sellerTax }}@endif
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
							<strong>{{ $buyer['name'] }}</strong>
							@if ($buyer['address'])<div>{{ $buyer['address'] }}</div>@endif
							@if ($buyer['tax'])<div>Adószám: {{ $buyer['tax'] }}</div>@endif
							@if ($buyer['number'])<div>Azonosító: {{ $buyer['number'] }}</div>@endif
						</div>

						<table class="lines">
							<thead>
								<tr><th>Megnevezés</th><th>Menny.</th><th class="ta-right">Nettó</th><th class="ta-right">ÁFA</th><th class="ta-right">Bruttó</th></tr>
							</thead>
							<tbody>
								@foreach ($inv['lines'] as $l)
									<tr>
										<td class="p-line-name"><strong>{{ $l['name'] }}</strong></td>
										<td>{{ $l['qty'] }}</td>
										<td class="ta-right">{{ $fmt($l['net']) }}</td>
										<td class="ta-right">{{ $fmt($l['vat']) }}</td>
										<td class="ta-right"><strong>{{ $fmt($l['gross']) }}</strong></td>
									</tr>
								@endforeach
							</tbody>
						</table>

						<div class="totals">
							<div class="row"><span>Nettó</span><span>{{ $fmt($inv['net']) }}</span></div>
							<div class="row"><span>ÁFA</span><span>{{ $fmt($inv['vat']) }}</span></div>
							<div class="row big"><span>Bruttó összesen</span><span>{{ $fmt($inv['amount']) }}</span></div>
						</div>

						<div class="foot">
							<strong>Fizetési mód:</strong> banki átutalás · {{ $sellerName }} · IBAN {{ $bank['iban'] }} · Közlemény: <strong>{{ $inv['id'] }}</strong>
						</div>
					</div>
				</div>
			</div>
		@endforeach

	</div>

@endsection
