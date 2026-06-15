{{--
	Legal page (Adatvédelem / ÁSZF) - CMS-driven from the portal's web_sections
	(LegalController). $title + $body come pre-resolved; $body is null until the
	rgadmin Ügyfélkapu CMS editor fills the section in (placeholder shown).
--}}
@extends('layouts.page')

@section('title', $title . ' - Royal Telekom Ügyfélkapu')

@section('content')

	<section class="rt-section p-legal">
		<div class="rt-container">
			<h1 class="p-legal-title">{{ $title }}</h1>

			<div class="p-legal-content">
				@if ($body)
					{!! $body !!}
				@else
					<p class="p-legal-empty">
						Az oldal tartalma feltöltés alatt áll. Kérdés esetén kérjük, forduljon
						ügyfélszolgálatunkhoz.
					</p>
				@endif
			</div>
		</div>
	</section>

@endsection
