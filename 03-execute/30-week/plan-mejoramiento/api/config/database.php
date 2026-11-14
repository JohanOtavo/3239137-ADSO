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

    /*
     * The history is pinned to public, schema and all.
     *
     * An unqualified name is resolved through the search path, which is
     * "filmgear,public". Before the first migration the project schema does
     * not exist, so the table landed in public; afterwards the same name
     * resolved to filmgear, where there was no history, and the API decided
     * nothing had ever run and tried to create every table again. The
     * container died on "relation category already exists" at every restart
     * after the first.
     *
     * Naming the schema makes the lookup answer the same thing on the first
     * boot and on the hundredth.
     */
    'migrations' => ['table' => 'public.migrations'],
];
