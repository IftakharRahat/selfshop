<?php

// SSLCommerz configuration

$apiDomain = env('SSLCZ_TESTMODE') ? "https://sandbox.sslcommerz.com" : "https://securepay.sslcommerz.com";
return [
	'apiCredentials' => [
		'store_id' => env("SSLCZ_STORE_ID"),
		'store_password' => env("SSLCZ_STORE_PASSWORD"),
	],
	'apiUrl' => [
		'make_payment' => "/gwprocess/v4/api.php",
		'transaction_status' => "/validator/api/merchantTransIDvalidationAPI.php",
		'order_validate' => "/validator/api/validationserverAPI.php",
		'refund_payment' => "/validator/api/merchantTransIDvalidationAPI.php",
		'refund_status' => "/validator/api/merchantTransIDvalidationAPI.php",
	],
	'apiDomain' => $apiDomain,
	'connect_from_localhost' => env("IS_LOCALHOST", false), // For Sandbox, use "true", For Live, use "false"

	// Base URL for SSLCommerz callback endpoints.
	// Must point to the Laravel backend, NOT the frontend.
	// Falls back to ASSET_URL (api domain) then APP_URL.
	'callback_base_url' => env('SSLCZ_CALLBACK_URL', env('ASSET_URL', env('APP_URL'))),

	'success_url' => '/success',
	'failed_url' => '/fail',
	'cancel_url' => '/cancel',
	'ipn_url' => '/ipn',
];
