{{--
	Plans (Szerződéseim) - the customer's current telecom contracts (detail cards
	with loyalty bar) + the publicly available packages showcase. Current
	contracts come from the linked customer's subscriptions (App\Support\WebContracts;
	an unlinked account is redirected to the dashboard); the available packages come from the shared
	product_listings (App\Support\WebPackages - the SAME source as the rgtelekom
	/csomagok page). There is NO plan switching on the portal (informational only).
--}}
@extends('layouts.portal')

@section('title', 'Szerződéseim - Royal Telekom Ügyfélkapu')

@section('content')

	@php
		$fmt   = fn ($n) => \App\Support\PortalMockData::huf($n);
		$fdate = fn ($d) => \App\Support\PortalMockData::date($d);

		$catMeta = [
			'akcio'    => ['label' => 'Akciók', 'icon' => 'tag'],
			'internet' => ['label' => 'Internet', 'icon' => 'wifi'],
			'telefon'  => ['label' => 'Telefon', 'icon' => 'phone'],
		];
	@endphp

	<div class="p-page">

		{{-- ADD CONTRACT (Szerződés hozzárendelése) - at the very top of Szerződéseim --}}
		@include('partials._add-contract')

		{{-- CURRENT CONTRACTS --}}
		<div class="p-card p-pad">
			<div class="p-section-title">
				<div>
					<div class="eyebrow">Aktív szerződéseim</div>
					<h3>Az Ön jelenlegi csomagjai</h3>
				</div>
			</div>
			@if (count($contracts))
				<div class="p-contract-detail-grid">
					@foreach ($contracts as $c)
						<div class="p-contract-detail {{ $c['status'] === 'pending' ? 'is-pending' : '' }}">
							@if ($c['status'] === 'pending')
								<div class="pending-banner">
									<i data-lucide="info" class="lucide-sm"></i> Jóváhagyásra vár - munkatársunk 1-2 munkanapon belül elbírálja.
								</div>
							@endif
							<div class="head">
								<div class="ico" data-kind="{{ $c['kind'] }}"><i data-lucide="{{ $c['icon'] }}" class="lucide-lg"></i></div>
								<div>
									<div class="kind">{{ $c['kindLabel'] }}</div>
									<h3>{{ $c['name'] }}</h3>
								</div>
								@if ($c['status'] === 'active')
									<span class="p-badge p-badge-success">Aktív</span>
								@elseif ($c['status'] === 'pending')
									<span class="p-badge p-badge-warn">Jóváhagyásra vár</span>
								@else
									<span class="p-badge p-badge-neutral">Szüneteltetve</span>
								@endif
							</div>
							<div class="metrics">
								@foreach ($c['metrics'] as $m)
									<div>
										<div class="lbl">{{ $m['label'] }}</div>
										<div class="val">{{ $m['value'] }}</div>
									</div>
								@endforeach
							</div>
							@if (! empty($c['loyalty']))
								<div class="loyalty">
									<div class="bar">
										<div class="fill" style="--pct: {{ $c['loyalty']['percent'] }}%"></div>
									</div>
									<div class="meta">
										<span>Hűségidő · {{ $c['loyalty']['percent'] }}%</span>
										<span>vége: <strong>{{ $fdate($c['loyalty']['end']) }}</strong></span>
									</div>
									@if (! empty($c['loyalty']['alert']))
										<div class="alert">
											<i data-lucide="alert-circle" class="lucide-sm"></i> {{ $c['loyalty']['alert'] }}
										</div>
									@endif
								</div>
							@endif
							<div class="footrow">
								<div class="price">
									<span class="amt">{{ $fmt($c['monthly']) }}</span>
									<span class="lbl">/ hó</span>
								</div>
							</div>
						</div>
					@endforeach
				</div>
			@else
				<div class="p-empty">
					<div class="ico"><i data-lucide="package" class="lucide-xl"></i></div>
					<h4>Még nincs aktív szerződése</h4>
					<p>Ha már ügyfelünk, rendelje a fiókjához a szerződését a fenti űrlappal.</p>
				</div>
			@endif
		</div>

		{{-- AVAILABLE PLANS (from the published web packages) --}}
		@if (! empty($plans))
			<div class="p-card p-pad">
				<div class="p-section-title">
					<div>
						<h3>Jelenleg elérhető csomagjaink</h3>
					</div>
				</div>
				<div class="p-plans-categories">
					@foreach ($plans as $cat => $catPlans)
						<div class="cat">
							<h4>
								<i data-lucide="{{ $catMeta[$cat]['icon'] ?? 'phone' }}" class="lucide-sm"></i>
								{{ $catMeta[$cat]['label'] ?? ucfirst($cat) }}
							</h4>
							<div class="cards">
								@foreach ($catPlans as $p)
									<div class="p-plan-mini {{ $p['featured'] ? 'featured' : '' }} {{ $p['current'] ? 'current' : '' }}">
										@if ($p['current'])
											<div class="ribbon">Aktuális</div>
										@elseif ($p['featured'])
											<div class="ribbon gold">Ajánlott</div>
										@endif
										<div class="kind">{{ $catMeta[$cat]['label'] ?? ucfirst($cat) }}</div>
										<h5>{{ $p['name'] }}</h5>
										@if ($p['heroNum'] !== '')
											<div class="hero-num">{{ $p['heroNum'] }}<span class="unit">{{ $p['heroUnit'] }}</span></div>
										@endif
										<ul>
											@foreach ($p['features'] as $f)
												<li><i data-lucide="check" class="lucide-xs"></i> {{ $f }}</li>
											@endforeach
										</ul>
										<div class="price-row">
											<span class="price">{{ $fmt($p['monthly']) }}<span class="per">/hó</span></span>
										</div>
									</div>
								@endforeach
							</div>
						</div>
					@endforeach
				</div>
				<p class="p-plans-note">
					<i data-lucide="info" class="lucide-sm"></i>
					Csomagváltáshoz vagy új szolgáltatás igényléséhez kérjük, vegye fel a kapcsolatot ügyfélszolgálatunkkal.
				</p>
			</div>
		@endif

	</div>

@endsection
