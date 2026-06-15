{{--
	Documents (Dokumentumok) - ported from portal-docs.jsx (PortalDocsPage).
	Category filters + document list + a "how to use these" help section.
	Filtering via Alpine; downloads are visual (programming phase). Mock data
	via PortalMockData::docs().
--}}
@extends('layouts.portal')

@section('title', 'Dokumentumok - Royal Telekom Ügyfélkapu')

@section('content')

	@php
		$iconFor = fn ($type) => match ($type) {
			'aszf'  => 'scroll-text',
			'legal' => 'shield',
			'tech'  => 'cpu',
			'form'  => 'clipboard-list',
			default => 'file-text',
		};
		$fmtDate = fn ($iso) => $iso ? str_replace('-', '. ', $iso) . '.' : '';

		$cats = [
			['id' => 'all',     'label' => 'Mind',          'count' => count($docs)],
			['id' => 'mine',    'label' => 'Saját irataim',  'count' => count(array_filter($docs, fn ($d) => $d['cat'] === 'mine'))],
			['id' => 'main',    'label' => 'Szerződéses',    'count' => count(array_filter($docs, fn ($d) => $d['cat'] === 'main'))],
			['id' => 'legal',   'label' => 'Jogi',           'count' => count(array_filter($docs, fn ($d) => $d['cat'] === 'legal'))],
			['id' => 'support', 'label' => 'Műszaki',        'count' => count(array_filter($docs, fn ($d) => $d['cat'] === 'support'))],
		];

		// Help cards from the CMS (documents.help repeater); fall back to the
		// built-in three before the editor has been opened/seeded.
		$help = $cms->items('documents.help');
		if (empty($help)) {
			$help = [
				['icon' => 'pen-line',   'title' => 'Szerződéskötés', 'description' => 'Töltse le, írja alá két példányban, hozza be irodánkba. Az átvételkor segítünk a kitöltésben.'],
				['icon' => 'refresh-cw', 'title' => 'Módosítás',      'description' => 'Csomagváltás vagy adatváltozás esetén a Szerződés-módosítás formanyomtatványt használja.'],
				['icon' => 'log-out',    'title' => 'Felmondás',      'description' => 'A felmondási nyomtatványt 30 nappal a kívánt megszűnés előtt juttassa el hozzánk.'],
			];
		}
	@endphp

	<div class="p-page" x-data="{ filter: 'all' }">

		<div class="p-card p-pad">
			<div class="p-section-title">
				<div>
					<div class="eyebrow">{{ $cms->get('documents.intro.eyebrow') ?: 'Dokumentumok' }}</div>
					<h3>{{ $cms->get('documents.intro.heading') ?: 'Letölthető dokumentumok' }}</h3>
				</div>
			</div>
			<p class="p-section-desc">{!! $cms->get('documents.intro.description') !== '' ? $cms->text('documents.intro.description') : 'Saját szerződései, szerződésminták, formanyomtatványok, ÁSZF és műszaki tájékoztatók egy helyen. Aláírt példányokat e-mailen vagy postai úton fogadunk.' !!}</p>

			<div class="p-doc-filters">
				@foreach ($cats as $c)
					<button type="button" class="p-doc-filter" :class="{ on: filter === '{{ $c['id'] }}' }" @click="filter = '{{ $c['id'] }}'">
						{{ $c['label'] }}
						<span class="count">{{ $c['count'] }}</span>
					</button>
				@endforeach
			</div>

			<div class="p-doc-list">
				@foreach ($docs as $d)
					<a href="#" class="p-doc-row" x-show="filter === 'all' || filter === '{{ $d['cat'] }}'">
						<span class="p-doc-ico {{ ! empty($d['personal']) ? 'personal' : '' }}">
							<i data-lucide="{{ $iconFor($d['type']) }}" class="lucide-md"></i>
						</span>
						<div class="p-doc-name">
							<div class="t">
								{{ $d['name'] }}
								@if (! empty($d['personal']))
									<span class="p-doc-tag">Saját</span>
								@endif
							</div>
							<div class="s">
								{{ $d['file'] }}
								@if (! empty($d['date']))
									<span class="dot">·</span>
									<span>{{ $fmtDate($d['date']) }}</span>
								@endif
							</div>
						</div>
						<div class="p-doc-meta">{{ $d['size'] }}</div>
						<i data-lucide="download" class="lucide-md p-doc-dl"></i>
					</a>
				@endforeach
			</div>

			<div class="p-doc-note">
				@if ($cms->get('documents.intro.note') !== '')
					{!! $cms->rich('documents.intro.note') !!}
				@else
					<p>Az aláírt példányokat <a href="mailto:info@royaltelekom.hu">info@royaltelekom.hu</a> címre küldje, vagy hozza be személyesen az Öv utcai irodánkba.</p>
				@endif
			</div>
		</div>

		<div class="p-card p-pad">
			<div class="p-section-title">
				<div>
					<div class="eyebrow">{{ $cms->get('documents.help.eyebrow') ?: 'Segítség' }}</div>
					<h3>{{ $cms->get('documents.help.heading') ?: 'Hogyan használja ezeket?' }}</h3>
				</div>
			</div>
			<div class="p-doc-help">
				@foreach ($help as $h)
					<div class="p-doc-help-card">
						<div class="ico"><i data-lucide="{{ $h['icon'] }}" class="lucide-md"></i></div>
						<h4>{{ $h['title'] }}</h4>
						<p>{{ $h['description'] }}</p>
					</div>
				@endforeach
			</div>
		</div>

	</div>

@endsection
