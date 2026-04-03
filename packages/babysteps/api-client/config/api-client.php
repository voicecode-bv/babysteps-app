<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Backend API Base URL
    |--------------------------------------------------------------------------
    |
    | The base URL of the backend API that the mobile app communicates with.
    |
    */

    'base_url' => env('API_BASE_URL', 'https://api.babysteps.app'),

    /*
    |--------------------------------------------------------------------------
    | API Timeout
    |--------------------------------------------------------------------------
    |
    | The maximum number of seconds to wait for an API response.
    |
    */

    'timeout' => env('API_TIMEOUT', 15),

    /*
    |--------------------------------------------------------------------------
    | Secure Storage Key
    |--------------------------------------------------------------------------
    |
    | The key used to store the API token in NativePHP secure storage.
    |
    */

    'token_key' => 'api_token',

];
