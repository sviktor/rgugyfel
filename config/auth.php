<?php

return [

	/*
	|--------------------------------------------------------------------------
	| Authentication Defaults
	|--------------------------------------------------------------------------
	|
	| The portal has a single audience: telecom customers. The default guard is
	| `customer` (session, backed by the cus_users table) and the default
	| password broker is `customers` (the cus_password_reset_tokens store).
	|
	*/

	// Single-audience portal: hardcoded so a stale AUTH_GUARD/AUTH_MODEL in an
	// older .env cannot point auth at a guard/model that no longer exists.
	'defaults' => [
		'guard' => 'customer',
		'passwords' => 'customers',
	],

	/*
	|--------------------------------------------------------------------------
	| Authentication Guards
	|--------------------------------------------------------------------------
	*/

	'guards' => [
		'customer' => [
			'driver' => 'session',
			'provider' => 'customers',
		],
	],

	/*
	|--------------------------------------------------------------------------
	| User Providers
	|--------------------------------------------------------------------------
	|
	| The portal authenticates against cus_users (App\Models\CustomerUser) -
	| the rgugyfel-owned login accounts, separate from the shared CRM
	| `customers` table (owned by rgadmin). A cus_users row links to a
	| customers row only once a staff member approves the contract request.
	|
	*/

	'providers' => [
		'customers' => [
			'driver' => 'eloquent',
			'model' => App\Models\CustomerUser::class,
		],
	],

	/*
	|--------------------------------------------------------------------------
	| Resetting Passwords
	|--------------------------------------------------------------------------
	|
	| The `customers` broker stores reset tokens in cus_password_reset_tokens.
	| Tokens expire after 30 minutes (matches the "a link 30 percig érvényes"
	| copy on the forgot-password screen).
	|
	*/

	'passwords' => [
		'customers' => [
			'provider' => 'customers',
			'table' => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'cus_password_reset_tokens'),
			'expire' => 30,
			'throttle' => 60,
		],
	],

	/*
	|--------------------------------------------------------------------------
	| Password Confirmation Timeout
	|--------------------------------------------------------------------------
	*/

	'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),

];
