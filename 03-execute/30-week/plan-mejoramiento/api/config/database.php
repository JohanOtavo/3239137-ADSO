<?php

return [
    'default' => env('DB_CONNECTION', 'pgsql'),

    'connections' => [
        'pgsql' => [
            'driver' => 'pgsql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '5432'),
            'database' => env('DB_DATABASE', 'filmgear'),
            'username' => env('DB_USERNAME', 'filmgear'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8',
            // The project schema comes first; public is left for extensions.
            'search_path' => env('DB_SCHEMA', 'filmgear').',public',
            'sslmode' => 'prefer',
        ],
    ],

    // The migration history lives in public so Laravel can find it before the
    // project schema exists.
    'migrations' => ['table' => 'migrations'],
];
