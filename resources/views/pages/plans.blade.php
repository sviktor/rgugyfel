{{--
	Plans (Szerződéseim) - ported from portal-plans-tickets.jsx (PlansPage +
	PlanSwitchFlow). Current contracts (detail cards with loyalty bar) +
	available plans showcase + a 3-step plan-switch wizard. The wizard steps
	+ plan selection are Alpine-driven (visual); the actual switch request is
	the programming phase. Mock data via PortalMockData.
--}}
@extends('layouts.portal')

@section('title', 'Szerződéseim - Royal Telekom Ügyfélkapu')

@section('content')

	@php
		$fmt   = fn ($n) => \App\Support\PortalMockData::huf($n);
		$fdate = fn ($d) => \App\Support\PortalMockData::date($d);

		$catMeta = [
			'internet' => ['label' => 'Internet', 'icon' => 'wifi'],
			'tv'       => ['label' => 'Televízió', 'icon' => 'tv'],
			'mobile'   => ['label' => 'Mobil', 'icon' => 'smartphone'],
		];
		$primary = $contracts[0] ?? ['name' => '', 'monthly' => 0, 'kind' => 'internet'];
	@endphp

	<div class="p-page"
	     x-data="{ sw: false, swStep: 1, swCat: 'internet', swCName: '', swCMonthly: 0, swPlan: null, swPlanName: '', swPlanMonthly: 0 }">

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
						<div class="footrow">
							<div class="price">
								<span class="amt">{{ $fmt($c['monthly']) }}</span>
								<span class="lbl">/ hó</span>
							</div>
							<button type="button" class="rt-btn rt-btn-primary"
							        @click="sw = true; swStep = 1; swCat = '{{ $c['kind'] === 'tv' ? 'internet' : $c['kind'] }}'; swCName = '{{ $c['name'] }}'; swCMonthly = {{ $c['monthly'] }}; swPlan = null">
								<i data-lucide="repeat" class="lucide-xs"></i> Csomagváltás
							</button>
						</div>
					</div>
				@endforeach
			</div>
		</div>

		{{-- AVAILABLE PLANS --}}
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
									<div class="kind">{{ $p['kind'] }}</div>
									<h5>{{ $p['name'] }}</h5>
									<div class="hero-num">{{ $p['heroNum'] }}<span class="unit">{{ $p['heroUnit'] }}</span></div>
									<ul>
										@foreach ($p['features'] as $f)
											<li><i data-lucide="check" class="lucide-xs"></i> {{ $f }}</li>
										@endforeach
									</ul>
									<div class="price-row">
										<span class="price">{{ $fmt($p['monthly']) }}<span class="per">/hó</span></span>
										@unless ($p['current'])
											<button type="button" class="rt-btn rt-btn-secondary p-btn-sm"
											        @click="sw = true; swStep = 2; swCat = '{{ $cat }}'; swCName = '{{ $primary['name'] }}'; swCMonthly = {{ $primary['monthly'] }}; swPlan = '{{ $p['id'] }}'; swPlanName = '{{ $p['name'] }}'; swPlanMonthly = {{ $p['monthly'] }}">
												Választom <i data-lucide="arrow-right" class="lucide-xs"></i>
											</button>
										@endunless
									</div>
								</div>
							@endforeach
						</div>
					</div>
				@endforeach
			</div>
		</div>

		{{-- PLAN-SWITCH WIZARD --}}
		<div class="p-modal-bg" x-show="sw" x-cloak @click="sw = false" x-transition.opacity>
			<div class="p-modal p-modal--lg" @click.stop>
				<div class="p-switch-body">
					<div class="p-switch-head">
						<div>
							<div class="eyebrow" x-text="'Csomagváltás · ' + swStep + '/3'"></div>
							<h3 x-text="swStep === 1 ? 'Válasszon új csomagot' : (swStep === 2 ? 'Tekintse át a változást' : 'Kérelem rögzítve')"></h3>
						</div>
						<button type="button" class="rt-btn rt-btn-ghost" @click="sw = false"><i data-lucide="x" class="lucide-md"></i></button>
					</div>
					<div class="p-switch-progress">
						<div :class="{ on: swStep >= 1 }"></div>
						<div :class="{ on: swStep >= 2 }"></div>
						<div :class="{ on: swStep >= 3 }"></div>
					</div>

					{{-- Step 1: pick --}}
					<div class="p-switch-pick" x-show="swStep === 1">
						<div class="current-row">
							<div class="lbl">Jelenlegi csomag</div>
							<strong x-text="swCName"></strong>
							<span x-text="swCMonthly.toLocaleString('hu-HU') + ' Ft / hó'"></span>
						</div>
						<div class="picks">
							@foreach ($plans as $cat => $catPlans)
								@foreach ($catPlans as $p)
									@unless ($p['current'])
										<button type="button" class="opt" x-show="swCat === '{{ $cat }}'"
										        :class="{ on: swPlan === '{{ $p['id'] }}' }"
										        @click="swPlan = '{{ $p['id'] }}'; swPlanName = '{{ $p['name'] }}'; swPlanMonthly = {{ $p['monthly'] }}">
											<div class="hero-num">{{ $p['heroNum'] }}<span class="unit">{{ $p['heroUnit'] }}</span></div>
											<div class="name">{{ $p['name'] }}</div>
											<div class="price">{{ $fmt($p['monthly']) }} <span>/ hó</span></div>
											<div class="diff">
												<span class="up" x-show="{{ $p['monthly'] }} >= swCMonthly" x-text="'+' + ({{ $p['monthly'] }} - swCMonthly).toLocaleString('hu-HU') + ' Ft havonta'"></span>
												<span class="down" x-show="{{ $p['monthly'] }} < swCMonthly" x-text="'−' + (swCMonthly - {{ $p['monthly'] }}).toLocaleString('hu-HU') + ' Ft havonta'"></span>
											</div>
										</button>
									@endunless
								@endforeach
							@endforeach
						</div>
						<div class="actions">
							<button type="button" class="rt-btn rt-btn-secondary rt-btn-large" @click="sw = false">Mégsem</button>
							<button type="button" class="rt-btn rt-btn-primary rt-btn-large" :disabled="!swPlan" @click="swStep = 2">
								Tovább <i data-lucide="arrow-right" class="lucide-sm"></i>
							</button>
						</div>
					</div>

					{{-- Step 2: confirm --}}
					<div class="p-switch-confirm" x-show="swStep === 2">
						<div class="diff-card">
							<div class="col before">
								<div class="lbl">Mostani</div>
								<h4 x-text="swCName"></h4>
								<div class="price"><span class="num" x-text="swCMonthly.toLocaleString('hu-HU') + ' Ft'"></span><span>/hó</span></div>
							</div>
							<i data-lucide="arrow-right" class="lucide-xl"></i>
							<div class="col after">
								<div class="lbl">Új csomag</div>
								<h4 x-text="swPlanName"></h4>
								<div class="price"><span class="num" x-text="swPlanMonthly.toLocaleString('hu-HU') + ' Ft'"></span><span>/hó</span></div>
							</div>
						</div>
						<ul class="terms">
							<li><i data-lucide="calendar" class="lucide-sm"></i> A váltás a következő számlázási ciklustól lép életbe ({{ $fdate('2026-06-01') }}).</li>
							<li><i data-lucide="shield" class="lucide-sm"></i> Az új csomag 1 év hűségidőhöz kötött, e nélkül nincs kedvezményes ár.</li>
							<li><i data-lucide="zap" class="lucide-sm"></i> Az átállás zökkenőmentes, internetkimaradás nélkül történik.</li>
							<li><i data-lucide="info" class="lucide-sm"></i> A művelet 24 órán belül visszavonható az ügyfélszolgálaton keresztül.</li>
						</ul>
						<label class="p-switch-terms">
							<input type="checkbox">
							<span>Elfogadom a csomagváltás feltételeit és tudomásul veszem, hogy a változás a következő havi számlán fog megjelenni.</span>
						</label>
						<div class="actions">
							<button type="button" class="rt-btn rt-btn-secondary rt-btn-large" @click="swStep = 1">
								<i data-lucide="arrow-left" class="lucide-sm"></i> Vissza
							</button>
							<button type="button" class="rt-btn rt-btn-primary rt-btn-large" @click="swStep = 3">Megerősítem a váltást</button>
						</div>
					</div>

					{{-- Step 3: done --}}
					<div class="p-switch-done" x-show="swStep === 3">
						<div class="check"><i data-lucide="check" class="lucide-2xl"></i></div>
						<h4>Köszönjük, kérelmét rögzítettük.</h4>
						<p>Munkatársunk 24 órán belül e-mailben visszaigazolja a váltást. Az új csomag <strong>{{ $fdate('2026-06-01') }}-től</strong> él.</p>
						<div class="summary">
							<div><span>Mostani csomag</span><strong x-text="swCName"></strong></div>
							<div><span>Új csomag</span><strong x-text="swPlanName"></strong></div>
							<div><span>Új havi díj</span><strong x-text="swPlanMonthly.toLocaleString('hu-HU') + ' Ft'"></strong></div>
						</div>
						<button type="button" class="rt-btn rt-btn-primary rt-btn-large" @click="sw = false">Bezárás</button>
					</div>
				</div>
			</div>
		</div>

	</div>

@endsection
