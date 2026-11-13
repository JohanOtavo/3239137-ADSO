<?php

return [
    // Business numbers live here and nowhere else (BR-2).
    'rental_days' => 4,
    'max_open_equipment_per_producer' => 2,
    'max_items_per_rental' => 2,
    'page_size_max' => 50,
    'page_size_default' => 20,

    // Eight hour token (AC-01.1).
    'token_ttl_seconds' => 28800,
    'jwt_secret' => env('JWT_SECRET'),

    // Photos: JPEG and PNG, 2 MB (AC-03.4).
    'photo_max_bytes' => 2097152,
    'photo_mime_types' => ['image/jpeg', 'image/png'],

    // The first administrator comes from the environment, never from the
    // database: only the hashing port can produce the hash (data-model 9).
    'admin_username' => env('ADMIN_USERNAME'),
    'admin_password' => env('ADMIN_PASSWORD'),
];
