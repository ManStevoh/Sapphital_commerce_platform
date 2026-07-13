<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Storefront base URL for RSS links
    |--------------------------------------------------------------------------
    |
    | Override in production, e.g. https://{slug}.shops.sapphital.africa
    | When empty, derived from tenant slug or request host.
    |
    */
    'storefront_base_url' => env('CMS_STOREFRONT_BASE_URL', ''),
];
