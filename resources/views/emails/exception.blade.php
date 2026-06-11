<!DOCTYPE html>
<html lang="hu">
<head>
	<meta charset="utf-8">
	<style>
		body { font-family: Arial, Helvetica, sans-serif; font-size: 14px; color: #222; }
		h1 { font-size: 18px; color: #b00020; margin: 0 0 12px; }
		table.meta { border-collapse: collapse; margin: 0 0 12px; }
		table.meta th { text-align: left; padding: 4px 12px 4px 0; vertical-align: top; white-space: nowrap; }
		table.meta td { padding: 4px 0; vertical-align: top; }
		pre { background: #f5f5f5; border: 1px solid #ddd; padding: 10px; overflow: auto; white-space: pre-wrap; word-break: break-word; font-size: 12px; }
	</style>
</head>
<body>
	@php
		// Redact sensitive fields from the request dump (mirror the framework $dontFlash list).
		$redact = ['password', 'password_confirmation', 'current_password', '_token'];
		$input  = [];
		if ($request) {
			$input = $request->all();
			foreach ($redact as $field) {
				if (array_key_exists($field, $input)) {
					$input[$field] = '***';
				}
			}
		}
	@endphp

	<h1>500-as hiba történt</h1>

	<table class="meta">
		<tr><th>Alkalmazás</th><td>{{ config('app.name') }}</td></tr>
		@if ($request)
			<tr><th>URL</th><td>{{ $request->fullUrl() }}</td></tr>
			<tr><th>Módszer</th><td>{{ $request->method() }}</td></tr>
			<tr><th>IP</th><td>{{ $request->ip() }}</td></tr>
		@else
			<tr><th>Környezet</th><td>Console / nincs HTTP kérés</td></tr>
		@endif
		<tr><th>Időpont</th><td>{{ now()->format('Y-m-d H:i:s') }}</td></tr>
	</table>

	<p><strong>Hibaüzenet:</strong> {{ $e->getMessage() }}</p>
	<p><strong>Típus:</strong> {{ $e::class }}</p>
	<p><strong>Fájl:</strong> {{ $e->getFile() }} ({{ $e->getLine() }})</p>

	@if ($request && ! empty($input))
		<p><strong>Kérés adatok:</strong></p>
		<pre>{{ print_r($input, true) }}</pre>
	@endif

	<p><strong>Stack trace:</strong></p>
	<pre>{{ $e->getTraceAsString() }}</pre>
</body>
</html>
