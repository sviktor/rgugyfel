{{--
	Dashboard — portal home page (MVP).
	The full design (467-line portal-dashboard.jsx) ports later — for now
	a focused 3-card overview: next invoice, active contracts, open tickets.
--}}
@extends('layouts.portal')

@section('title', 'Főoldal — Royal Telekom Ügyfélkapu')

@section('content')

	<div class="p-page" style="display:grid;gap:20px;">

		{{-- Top row: overdue invoice highlight + open tickets badge --}}
		<div style="display:grid;grid-template-columns:1.4fr 1fr;gap:20px;">

			{{-- Overdue invoice card --}}
			@php $overdue = collect($invoices)->firstWhere('status', 'overdue'); @endphp
			<div class="p-card" style="padding:24px 26px;">
				<div class="eyebrow" style="font-size:11px;font-weight:600;color:var(--rt-gold-700);text-transform:uppercase;letter-spacing:0.16em;">Számla státusz</div>
				@if ($overdue)
					<h3 style="font-family:var(--rt-font-display);font-size:24px;color:var(--p-fg-1);margin:8px 0 4px;">
						Lejárt számla: {{ number_format($overdue['amount'], 0, ',', ' ') }} Ft
					</h3>
					<p style="font-size:14px;color:var(--p-fg-2);margin:0 0 18px;line-height:1.6;">
						{{ $overdue['service'] }} · esedékes volt: {{ $overdue['due'] }}
					</p>
					<a href="{{ route('invoices') }}" class="rt-btn rt-btn-primary">
						Számla megtekintése <i data-lucide="arrow-right" class="lucide-sm"></i>
					</a>
				@else
					<h3 style="font-family:var(--rt-font-display);font-size:24px;color:var(--p-fg-1);margin:8px 0 4px;">
						Nincs lejárt számla
					</h3>
					<p style="font-size:14px;color:var(--p-fg-2);margin:0;">Köszönjük a pontos fizetést!</p>
				@endif
			</div>

			{{-- Open tickets card --}}
			@php $openTickets = collect($tickets)->where('status', 'open')->count(); @endphp
			<div class="p-card" style="padding:24px 26px;">
				<div class="eyebrow" style="font-size:11px;font-weight:600;color:var(--rt-gold-700);text-transform:uppercase;letter-spacing:0.16em;">Hibajegyek</div>
				<h3 style="font-family:var(--rt-font-display);font-size:24px;color:var(--p-fg-1);margin:8px 0 4px;">
					{{ $openTickets }} nyitott bejelentés
				</h3>
				<p style="font-size:14px;color:var(--p-fg-2);margin:0 0 18px;line-height:1.6;">
					Az ügyintézőink dolgoznak rajta. A részletekért nézze meg az ügyintézés oldalt.
				</p>
				<a href="{{ route('tickets') }}" class="rt-btn rt-btn-secondary">
					Hibajegyek <i data-lucide="arrow-right" class="lucide-sm"></i>
				</a>
			</div>
		</div>

		{{-- Active contracts list --}}
		<div class="p-card" style="padding:24px 26px;">
			<div class="p-section-title" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
				<div>
					<div class="eyebrow" style="font-size:11px;font-weight:600;color:var(--rt-gold-700);text-transform:uppercase;letter-spacing:0.16em;">Szerződéseim</div>
					<h3 style="font-family:var(--rt-font-display);font-size:22px;color:var(--p-fg-1);margin:6px 0 0;">Aktív szolgáltatások</h3>
				</div>
				<a href="{{ route('plans') }}" class="rt-btn rt-btn-ghost">Összes <i data-lucide="arrow-right" class="lucide-sm"></i></a>
			</div>

			<div style="display:grid;gap:12px;">
				@foreach ($contracts as $c)
					<div style="display:grid;grid-template-columns:auto 1fr auto;gap:18px;align-items:center;padding:14px 0;border-top:1px solid var(--p-border);">
						<div style="width:42px;height:42px;background:var(--rt-navy-50);border:1px solid var(--p-border);display:inline-flex;align-items:center;justify-content:center;color:var(--rt-navy-800);">
							<i data-lucide="{{ $c['icon'] }}" class="lucide-md"></i>
						</div>
						<div>
							<div style="font-family:var(--rt-font-display);font-size:17px;font-weight:600;color:var(--p-fg-1);">{{ $c['name'] }}</div>
							<div style="font-size:13px;color:var(--p-fg-2);margin-top:2px;">{{ $c['address'] }}</div>
						</div>
						<div style="font-family:var(--rt-font-display);font-size:18px;font-weight:600;color:var(--rt-navy-800);">
							{{ number_format($c['monthly'], 0, ',', ' ') }} Ft / hó
						</div>
					</div>
				@endforeach
			</div>
		</div>

	</div>

@endsection
