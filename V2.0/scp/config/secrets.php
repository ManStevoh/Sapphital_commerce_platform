<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Secrets Driver (ADR-007 Phase 1)
    |--------------------------------------------------------------------------
    |
    | Phase 1 uses a file-based vault under storage/secrets (never committed).
    | Phase 2 will add HashiCorp Vault / cloud KMS drivers.
    |
    */

    'driver' => env('SECRETS_DRIVER', 'file'),

    'paths' => [
        'default' => env('SECRETS_FILE_PATH', storage_path('secrets')),
    ],
];
