<?php

declare(strict_types=1);

return [
  'enabled' => env('TURNSTILE_ENABLED', env('TURNSTILE_SECRET_KEY') !== null),
  'secret_key' => env('TURNSTILE_SECRET_KEY'),
  'site_key' => env('TURNSTILE_SITE_KEY'),
  'verify_url' => 'https://challenges.cloudflare.com/turnstile/v0/siteverify',
];
