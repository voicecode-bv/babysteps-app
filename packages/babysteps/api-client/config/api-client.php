<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application Platform
    |--------------------------------------------------------------------------
    |
    | The platform the application is running on. When set to "app", the API
    | token is stored in NativePHP secure storage. When set to "web", the
    | token is stored in the session.
    |
    | Supported: "web", "app"
    |
    */

    'platform' => env('APP_PLATFORM', 'web'),

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
