{{--
	Tickets (Hibabejelentés) - ported from portal-plans-tickets.jsx
	(TicketsPage + NewTicketModal + TicketDetailModal). Open/closed tabs +
	ticket list + new-ticket modal (segmented pickers) + per-ticket detail
	modal with the message thread. Tabs/modals via Alpine; submitting/replying
	is the programming phase. Mock data via PortalMockData.
--}}
@extends('layouts.portal')

@section('title', 'Hibabejelentés - Royal Telekom Ügyfélkapu')

@section('content')

	@php
		$fdate  = fn ($d) => \App\Support\PortalMockData::date($d);
		$open   = array_values(array_filter($tickets, fn ($t) => $t['status'] === 'open'));
		$closed = array_values(array_filter($tickets, fn ($t) => $t['status'] === 'closed'));
	@endphp

	<div class="p-page" x-data="{ tab: 'open', creating: false, opened: null }">

		<div class="p-tabs-head">
			<div class="p-tabs">
				<button type="button" :class="{ on: tab === 'open' }" @click="tab = 'open'">
					Nyitott hibák <span class="cnt">{{ count($open) }}</span>
				</button>
				<button type="button" :class="{ on: tab === 'closed' }" @click="tab = 'closed'">
					Lezárt hibák <span class="cnt">{{ count($closed) }}</span>
				</button>
			</div>
			<button type="button" class="rt-btn rt-btn-primary" @click="creating = true">
				<i data-lucide="plus" class="lucide-sm"></i> Új hibabejelentés
			</button>
		</div>

		<div class="p-tickets">
			@if (count($open) === 0)
				<div class="p-card" x-show="tab === 'open'">
					<div class="p-empty">
						<div class="ico"><i data-lucide="check-circle" class="lucide-xl"></i></div>
						<h4>Nincs nyitott hibajegy</h4>
						<p>Ha problémát tapasztal, jelentse be itt - 24 órán belül felvesszük a kapcsolatot.</p>
					</div>
				</div>
			@endif
			@if (count($closed) === 0)
				<div class="p-card" x-show="tab === 'closed'">
					<div class="p-empty">
						<div class="ico"><i data-lucide="archive" class="lucide-xl"></i></div>
						<h4>Még nincs lezárt hibajegy</h4>
						<p>A lezárt hibajegyek itt fognak megjelenni.</p>
					</div>
				</div>
			@endif

			@foreach ($tickets as $t)
				<button type="button" class="p-ticket-row" x-show="tab === '{{ $t['status'] }}'" @click="opened = '{{ $t['id'] }}'">
					<div class="ico" data-priority="{{ $t['priority'] }}">
						<i data-lucide="{{ $t['priority'] === 'high' ? 'alert-triangle' : 'alert-circle' }}" class="lucide-md"></i>
					</div>
					<div class="body">
						<div class="row1">
							<span class="num">#{{ $t['id'] }}</span>
							<strong>{{ $t['subject'] }}</strong>
							@if ($t['priority'] === 'high')
								<span class="p-badge p-badge-danger">Magas</span>
							@else
								<span class="p-badge p-badge-warn">Normál</span>
							@endif
							@if ($t['status'] === 'closed')
								<span class="p-badge p-badge-neutral">Lezárva</span>
							@endif
						</div>
						<div class="row2">
							<span><i data-lucide="calendar" class="lucide-xs"></i> Megnyitva {{ $fdate($t['opened']) }}</span>
							@if (! empty($t['closed']))
								<span><i data-lucide="check" class="lucide-xs"></i> Lezárva {{ $fdate($t['closed']) }}</span>
							@endif
							<span><i data-lucide="message-circle" class="lucide-xs"></i> {{ count($t['messages']) }} üzenet</span>
							<span><i data-lucide="user" class="lucide-xs"></i> {{ $t['assignee'] }}</span>
						</div>
						<p>{{ $t['lastMessage'] }}</p>
					</div>
					<i data-lucide="chevron-right" class="lucide-md chev"></i>
				</button>
			@endforeach
		</div>

		{{-- NEW TICKET MODAL --}}
		<div class="p-modal-bg" x-show="creating" x-cloak @click="creating = false" x-transition.opacity>
			<div class="p-modal p-modal--md" @click.stop x-data="{ cat: 'internet', prio: 'normal' }">
				<form class="p-newticket" @submit.prevent="creating = false">
					<div class="head">
						<div>
							<div class="eyebrow">Új hibabejelentés</div>
							<h3>Mit tapasztal?</h3>
						</div>
						<button type="button" class="rt-btn rt-btn-ghost" @click="creating = false"><i data-lucide="x" class="lucide-md"></i></button>
					</div>

					<div class="p-field">
						<label>Hiba típusa</label>
						<div class="p-segmented">
							@foreach ([['internet', 'Internet', 'wifi'], ['tv', 'TV', 'tv'], ['mobile', 'Mobil', 'smartphone'], ['phone', 'Vezetékes', 'phone'], ['billing', 'Számlázás', 'receipt'], ['other', 'Egyéb', 'help-circle']] as [$v, $l, $ic])
								<button type="button" :class="{ on: cat === '{{ $v }}' }" @click="cat = '{{ $v }}'">
									<i data-lucide="{{ $ic }}" class="lucide-xs"></i> {{ $l }}
								</button>
							@endforeach
						</div>
					</div>

					<div class="p-field">
						<label for="nt_subject">Tárgy</label>
						<input id="nt_subject" class="rt-input" required placeholder="Pl. Lassú internet a délutáni órákban">
					</div>

					<div class="p-field">
						<label>Prioritás</label>
						<div class="p-segmented">
							@foreach ([['low', 'Alacsony'], ['normal', 'Normál'], ['high', 'Magas (sürgős)']] as [$v, $l])
								<button type="button" :class="{ on: prio === '{{ $v }}' }" @click="prio = '{{ $v }}'">{{ $l }}</button>
							@endforeach
						</div>
					</div>

					<div class="p-field">
						<label for="nt_address">Érintett cím</label>
						<input id="nt_address" class="rt-input" placeholder="1145 Budapest, Aranyhegyi út 14.">
					</div>

					<div class="p-field">
						<label for="nt_desc">Részletes leírás</label>
						<textarea id="nt_desc" class="rt-input" rows="5" required placeholder="Mikor kezdődött, mit látott, milyen eszközön, mit próbált már…"></textarea>
						<span class="hint">A pontos leírással gyorsabban dolgozhatunk.</span>
					</div>

					<div class="p-field">
						<label>Csatolmány (opcionális)</label>
						<div class="p-upload">
							<i data-lucide="paperclip" class="lucide-sm"></i>
							<span>Fotó vagy képernyőkép a hibáról</span>
							<button type="button" class="rt-btn rt-btn-secondary p-btn-sm">Tallózás</button>
						</div>
					</div>

					<div class="actions">
						<button type="button" class="rt-btn rt-btn-secondary rt-btn-large" @click="creating = false">Mégsem</button>
						<button type="submit" class="rt-btn rt-btn-primary rt-btn-large p-btn-grow">
							<i data-lucide="send" class="lucide-sm"></i> Bejelentés elküldése
						</button>
					</div>
				</form>
			</div>
		</div>

		{{-- TICKET DETAIL MODALS (one per ticket) --}}
		@foreach ($tickets as $t)
			<div class="p-modal-bg" x-show="opened === '{{ $t['id'] }}'" x-cloak @click="opened = null" x-transition.opacity>
				<div class="p-modal p-ticket-detail" @click.stop>
					<div class="head">
						<div>
							<div class="eyebrow">#{{ $t['id'] }} · {{ $t['category'] }}</div>
							<h3>{{ $t['subject'] }}</h3>
							<div class="meta">
								@if ($t['priority'] === 'high')
									<span class="p-badge p-badge-danger">Magas prioritás</span>
								@else
									<span class="p-badge p-badge-warn">Normál</span>
								@endif
								@if ($t['status'] === 'open')
									<span class="p-badge p-badge-warn">Folyamatban</span>
								@else
									<span class="p-badge p-badge-neutral">Lezárva</span>
								@endif
								<span><i data-lucide="user" class="lucide-xs"></i> {{ $t['assignee'] }}</span>
								<span><i data-lucide="calendar" class="lucide-xs"></i> {{ $fdate($t['opened']) }}</span>
							</div>
						</div>
						<button type="button" class="rt-btn rt-btn-ghost" @click="opened = null"><i data-lucide="x" class="lucide-md"></i></button>
					</div>
					<div class="thread">
						@foreach ($t['messages'] as $m)
							<div class="msg {{ $m['from'] === 'customer' ? 'me' : 'them' }}">
								<div class="avatar">{{ $m['from'] === 'customer' ? 'KÉ' : 'RT' }}</div>
								<div class="bubble">
									<div class="head">
										<strong>{{ $m['from'] === 'customer' ? 'Kis Éva' : ($m['author'] ?? 'Royal Telekom') }}</strong>
										<span>{{ $fdate($m['at']) }} · {{ $m['time'] ?? '' }}</span>
									</div>
									<p>{{ $m['body'] }}</p>
								</div>
							</div>
						@endforeach
					</div>
					@if ($t['status'] === 'open')
						<form class="reply" @submit.prevent>
							<textarea placeholder="Válasz írása…" rows="3"></textarea>
							<div class="row">
								<button type="button" class="rt-btn rt-btn-ghost p-btn-sm"><i data-lucide="paperclip" class="lucide-xs"></i> Csatolás</button>
								<div class="p-spacer"></div>
								<button type="submit" class="rt-btn rt-btn-primary"><i data-lucide="send" class="lucide-xs"></i> Küldés</button>
							</div>
						</form>
					@endif
				</div>
			</div>
		@endforeach

	</div>

@endsection
