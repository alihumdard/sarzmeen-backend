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
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    // storage/* lets the admin read an uploaded image back into a canvas
    // to re-crop it; the browser can already display these files.
    'paths' => ['api/*', 'sanctum/csrf-cookie', 'storage/*'],

    'allowed_methods' => ['*'],

    /*
     * The site can be reached under more than one hostname — a bare IP and a
     * friendlier name for the same staging box, for instance — and only the
     * origin listed here can call the API. CORS_ALLOWED_ORIGINS takes a
     * comma-separated list for those cases; FRONTEND_URL alone still works,
     * and stays the one the mail links are built from.
     */
    'allowed_origins' => array_values(array_unique(array_filter(array_merge(
        array_map('trim', explode(',', trim((string) env('CORS_ALLOWED_ORIGINS')) !== ''
            ? (string) env('CORS_ALLOWED_ORIGINS')
            : (string) env('FRONTEND_URL', 'http://localhost:3000'))),
        ['http://127.0.0.1:3000'],
    )))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['Content-Type', 'X-Requested-With', 'Authorization', 'X-XSRF-TOKEN', 'Accept'],

    'exposed_headers' => [],

    'max_age' => 7200,

    'supports_credentials' => true,

];
