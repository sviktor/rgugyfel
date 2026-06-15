<?php

// Google reCAPTCHA v2 ("I'm not a robot") - gates the portal registration form.
// Toggle via RECAPTCHA_ENABLED; keys in .env (never commit them). Same shape as
// rgsite/rgtelekom (config/recaptcha.php) and rgadmin's login gate.
return [
	'enabled'    => (bool) env('RECAPTCHA_ENABLED', false),
	'site_key'   => env('RECAPTCHA_SITE_KEY', ''),
	'secret_key' => env('RECAPTCHA_SECRET_KEY', ''),
	'verify_url' => 'https://www.google.com/recaptcha/api/siteverify',
];
