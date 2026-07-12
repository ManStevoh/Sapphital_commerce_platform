<?php

return [

    'defaults' => [
        'guard' => env('AUTH_GUARD', 'merchant'),
        'passwords' => env('AUTH_PASSWORD_BROKER', 'merchant_users'),
    ],

    'guards' => [
        'merchant' => [
            'driver' => 'session',
            'provider' => 'merchant_users',
        ],
        'platform' => [
            'driver' => 'session',
            'provider' => 'platform_admins',
        ],
        'customer' => [
            'driver' => 'session',
            'provider' => 'customers',
        ],
        'api' => [
            'driver' => 'sanctum',
            'provider' => 'merchant_users',
        ],
    ],

    'providers' => [
        'merchant_users' => [
            'driver' => 'eloquent',
            'model' => Platform\Identity\Models\MerchantUser::class,
        ],
        'platform_admins' => [
            'driver' => 'eloquent',
            'model' => Platform\Identity\Models\PlatformAdmin::class,
        ],
        'customers' => [
            'driver' => 'eloquent',
            'model' => Platform\Identity\Models\Customer::class,
        ],
    ],

    'passwords' => [
        'merchant_users' => [
            'provider' => 'merchant_users',
            'table' => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
            'expire' => 60,
            'throttle' => 60,
        ],
        'platform_admins' => [
            'provider' => 'platform_admins',
            'table' => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
            'expire' => 60,
            'throttle' => 60,
        ],
        'customers' => [
            'provider' => 'customers',
            'table' => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),

];
