<?php

declare(strict_types=1);

return [
    'driver' => env('SECRETS_DRIVER', 'file'),

    'paths' => [
        'default' => env('SECRETS_FILE_PATH', storage_path('secrets')),
    ],
];
