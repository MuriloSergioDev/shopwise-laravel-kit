<?php

declare(strict_types=1);

return [
    'jwks_url' => env('IDENTITY_JWKS_URL', 'http://identity:8000/.well-known/jwks.json'),

    'issuer' => env('JWT_ISSUER', 'shopwise-identity'),

    'jwks_cache_seconds' => 300,

    'leeway_seconds' => 30,
];
