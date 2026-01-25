<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    */

    'allowed_origins' => env('CORS_ALLOWED_ORIGINS', 'http://localhost:4200'),

    'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],

    'allowed_headers' => ['Authorization', 'Content-Type', 'X-Request-ID', 'X-Client-App'],

    'allow_credentials' => true,
];
