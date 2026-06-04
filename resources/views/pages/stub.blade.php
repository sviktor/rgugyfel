{{--
	Generic "coming soon" stub page.
	Used for portal sections whose full UI is not yet ported:
	invoices, plans, usage, tickets, docs, profile.

	Expects:
	- $title  string  what the section will hold
	- $body   string  paragraph describing what's coming
--}}
@extends('layouts.portal')

@section('title', $title . ' — Royal Telekom Ügyfélkapu')

@section('content')

	<div class="p-page">
		<div class="p-card p-stub-card">
			<div class="eyebrow">Hamarosan</div>
			<h3>{{ $title }}</h3>
			<p>{{ $body }}</p>
		</div>
	</div>

@endsection
