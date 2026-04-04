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

    'base_url' => env('API_BASE_URL', 'https://babysteps-api.test/api/v1'),

    'timeout' => (int) env('API_TIMEOUT', 15),

    'token_key' => 'api_token',

];
