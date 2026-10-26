<?php

declare(strict_types=1);

namespace Shopwise\Kit\Auth;

use Illuminate\Contracts\Auth\Authenticatable;
use LogicException;

/**
 * The caller as described by a verified identity JWT. Services own no user table;
 * `$request->user()` returns this.
 */
final readonly class JwtUser implements Authenticatable
{
    /**
     * @param  list<string>  $roles
     */
    public function __construct(
        public string $id,
        public array $roles = [],
        public ?string $email = null,
    ) {}

    public function hasRole(string $role): bool
    {
        return in_array($role, $this->roles, true);
    }

    public function getAuthIdentifierName(): string
    {
        return 'id';
    }

    public function getAuthIdentifier(): string
    {
        return $this->id;
    }

    public function getAuthPasswordName(): string
    {
        return 'password';
    }

    public function getAuthPassword(): string
    {
        throw new LogicException('JWT users have no password.');
    }

    public function getRememberToken(): ?string
    {
        return null;
    }

    public function setRememberToken($value): void {}

    public function getRememberTokenName(): string
    {
        return '';
    }
}
