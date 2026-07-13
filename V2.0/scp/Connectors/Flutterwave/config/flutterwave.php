<?php

declare(strict_types=1);

return [
    'secret_key' => env('FLUTTERWAVE_SECRET_KEY'),
    'public_key' => env('FLUTTERWAVE_PUBLIC_KEY'),
    'secret_hash' => env('FLUTTERWAVE_SECRET_HASH'),
    'encryption_key' => env('FLUTTERWAVE_ENCRYPTION_KEY'),
];
