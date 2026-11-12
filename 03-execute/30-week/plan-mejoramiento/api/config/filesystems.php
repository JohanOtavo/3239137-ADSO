<?php

return [
    'default' => 'photos',

    'disks' => [
        // Equipment photos on local disk. Swapping this for S3 touches this
        // file and one line of the composition root, nothing else (OCP).
        'photos' => [
            'driver' => 'local',
            'root' => base_path(env('PHOTOS_DIR', 'storage/app/photos')),
            'throw' => true,
        ],
    ],
];
