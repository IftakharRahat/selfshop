<?php

return array(
    /*
	|--------------------------------------------------------------------------
	| One Signal App Id
	|--------------------------------------------------------------------------
	|
	|
	*/
    'app_id' => env('ONESIGNAL_APP_ID'),

    /*
	|--------------------------------------------------------------------------
	| Rest API Key
	|--------------------------------------------------------------------------
	|
    |
	|
	*/
    'rest_api_key' => env('ONESIGNAL_REST_API_KEY'),
    'user_auth_key' => env('USER_AUTH_KEY'),

    /*
	|--------------------------------------------------------------------------
	| Panel-specific OneSignal apps
	|--------------------------------------------------------------------------
	|
	| Use these when one backend serves both user and supplier panels but
	| each panel has its own OneSignal app credentials.
	|
	*/
    'panels' => [
        'user' => [
            'app_id' => env('ONESIGNAL_USER_APP_ID', env('ONESIGNAL_APP_ID')),
            'rest_api_key' => env('ONESIGNAL_USER_REST_API_KEY', env('ONESIGNAL_REST_API_KEY')),
        ],
        'supplier' => [
            'app_id' => env('ONESIGNAL_SUPPLIER_APP_ID', env('ONESIGNAL_APP_ID')),
            'rest_api_key' => env('ONESIGNAL_SUPPLIER_REST_API_KEY', env('ONESIGNAL_REST_API_KEY')),
        ],
    ],

    /*
	|--------------------------------------------------------------------------
	| OneSignal API base
	|--------------------------------------------------------------------------
	*/
    'api_base' => env('ONESIGNAL_API_BASE', 'https://api.onesignal.com'),

    /*
	|--------------------------------------------------------------------------
	| Guzzle Timeout
	|--------------------------------------------------------------------------
	|
    |
	|
	*/
    'guzzle_client_timeout' => env('ONESIGNAL_GUZZLE_CLIENT_TIMEOUT', 0),
);
