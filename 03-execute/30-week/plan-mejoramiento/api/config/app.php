<?php

return [
    'name' => env('APP_NAME', 'film-gear'),
    'env' => env('APP_ENV', 'production'),
    'debug' => (bool) env('APP_DEBUG', false),
    'url' => env('APP_URL', 'http://localhost'),

    // The server runs in UTC. Every stored instant is UTC (RN-6).
    'timezone' => 'UTC',
    'locale' => 'es',
    'fallback_locale' => 'es',

    'key' => env('APP_KEY'),
    'cipher' => 'AES-256-CBC',

    'maintenance' => ['driver' => 'file'],
];
