<?php

declare(strict_types=1);

namespace Shopwise\Kit\Auth;

use Firebase\JWT\Key;

/**
 * Where VerifyJwt gets the public keys that verify access tokens. Services use Jwks (HTTP);
 * identity binds its own signing keys so it never calls itself.
 */
interface KeySource
{
    /**
     * @return array<string, Key> keyed by "kid"
     */
    public function keys(bool $refresh = false): array;
}
