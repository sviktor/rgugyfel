{{--
	Documents (Dokumentumok) - ported from portal-docs.jsx (PortalDocsPage).
	Tag filters + document list + a "how to use these" help section. The intro +
	help cards are CMS-driven; the document LIST is the public portal library
	(App\Support\WebDocuments - the shared `documents` rows bound to
	web_documents/portal/documents, uploaded in rgadmin), each row linking to the
	gated docs.download route. Same library for every signed-in customer.
--}}
@extends('layouts.portal')

@section('title', 'Dokumentumok - Royal Telekom Ügyfélkapu')

@section('content')

	@php
		$fmtDate = fn ($iso) => $iso ? str_replace('-', '. ', $iso) . '.' : '';

		// Filter pills from the real document tags (Mind + one per distinct tag);
		// $tags comes from App\Support\WebDocuments::tags(). A document may carry
		// several tags, so a pill's count is the number of docs holding that tag.
		$cats = [['id' => 'all', 'label' => 'Mind', 'count' => count($docs)]];
		foreach ($tags ?? [] as $t) {
			$cats[] = [
				'id'    => $t,
				'label' => $t,
				'count' => count(array_filter($docs, fn ($d) => in_array($t, $d['tags'], true))),
			];
		}

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
			<p class="p-section-desc">{!! $cms->get('documents.intro.description') !== '' ? $cms->text('documents.intro.description') : 'Saját szerződései, szerződésminták, formanyomtatványok, ÁSZF és műszaki tájékoztatók egy helyen. Aláírt példányokat e-mailen, személyesen az irodánkban vagy postai úton fogadunk.' !!}</p>

			@if (count($docs))
				@if (count($cats) > 1)
					<div class="p-doc-filters">
						@foreach ($cats as $c)
							<button type="button" class="p-doc-filter" :class="{ on: filter === {{ \Illuminate\Support\Js::from($c['id']) }} }" @click="filter = {{ \Illuminate\Support\Js::from($c['id']) }}">
								{{ $c['label'] }}
								<span class="count">{{ $c['count'] }}</span>
							</button>
						@endforeach
					</div>
				@endif

				<div class="p-doc-list">
					@foreach ($docs as $d)
						<a href="{{ $d['url'] }}" class="p-doc-row" x-show="filter === 'all' || {{ \Illuminate\Support\Js::from($d['tags']) }}.includes(filter)">
							<span class="p-doc-ico">
								<i data-lucide="{{ $d['icon'] }}" class="lucide-md"></i>
							</span>
							<div class="p-doc-name">
								<div class="t">{{ $d['name'] }}</div>
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
			@endif

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
