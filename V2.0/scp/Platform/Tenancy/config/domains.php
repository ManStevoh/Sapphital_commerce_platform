<?php

declare(strict_types=1);

return [
    'cname_target' => env('SCP_CUSTOM_DOMAIN_CNAME', 'shops.sapphital.africa'),
    'txt_host_prefix' => '_sapphital-verify',
    'plan_limits' => [
        'starter' => 0,
        'growth' => 1,
        'pro' => 5,
        'default' => 0,
    ],
    'ssl_provider' => env('SCP_CUSTOM_DOMAIN_SSL_PROVIDER', 'fake'),
];
