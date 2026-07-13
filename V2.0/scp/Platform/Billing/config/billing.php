<?php

declare(strict_types=1);

return [
    'vat_rate' => (float) env('BILLING_VAT_RATE', 0.075),
    'platform_name' => env('BILLING_PLATFORM_NAME', 'SAPPHITAL Commerce Platform'),
];
