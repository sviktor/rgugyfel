<?php

return [

	// Master switch; on by default (fires in dev + prod per the user's choice).
	'enabled'  => (bool) env('ERROR_MAIL_ENABLED', true),

	// Comma-separated recipients; defaults to the szake addresses.
	'mail_to'  => env('ERROR_MAIL_TO', 'viktor.stranszki@gmail.com,vadgabika@gmail.com'),

	// Throttle window (seconds) for an identical exception signature.
	'throttle' => (int) env('ERROR_MAIL_THROTTLE', 300),

];
