<?php

declare(strict_types=1);

namespace Shopwise\Kit\Auth;

use Firebase\JWT\JWK;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Public keys of the identity service, fetched from its JWKS endpoint and cached.
 */
final class Jwks
{
    /**
     * @return array<string, Key>
     */
    public function keys(bool $refresh = false): array
    {
        if ($refresh) {
            Cache::forget('shopwise.jwks');
        }

        /** @var array{keys: list<array<string, mixed>>} $set */
        $set = Cache::remember(
            'shopwise.jwks',
            config()->integer('shopwise-auth.jwks_cache_seconds', 300),
            fn (): array => (array) Http::timeout(3)->get(config()->string('shopwise-auth.jwks_url'))->throw()->json(),
        );

        return JWK::parseKeySet($set, 'RS256');
    }
}
