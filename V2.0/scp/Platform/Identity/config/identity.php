<?php

declare(strict_types=1);

return [
  /*
    |--------------------------------------------------------------------------
    | Platform admin MFA
    |--------------------------------------------------------------------------
    |
    | When enabled, platform admins must enroll TOTP and verify on each login.
    | API tokens without the platform:admin ability are rejected on ops routes.
    |
    */
    'platform_mfa_enforced' => (bool) env('PLATFORM_MFA_ENFORCED', true),

    'platform_mfa_issuer' => env('PLATFORM_MFA_ISSUER', 'SAPPHITAL Platform'),

    /*
    |--------------------------------------------------------------------------
    | Merchant owner MFA
    |--------------------------------------------------------------------------
    |
    | When enabled, merchant Owner accounts must enroll TOTP and verify on each
    | login. Tokens without merchant:access are rejected on tenant routes.
    |
    */
    'merchant_mfa_enforced' => (bool) env('MERCHANT_MFA_ENFORCED', true),

    'merchant_mfa_issuer' => env('MERCHANT_MFA_ISSUER', 'SAPPHITAL Merchant'),
];
