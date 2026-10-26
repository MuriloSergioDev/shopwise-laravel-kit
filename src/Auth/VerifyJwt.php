<?php

declare(strict_types=1);

namespace Shopwise\Kit\Auth;

use Closure;
use Firebase\JWT\JWT;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Route middleware `auth.jwt` / `auth.jwt:admin`: verifies the bearer token issued by the identity
 * service against its JWKS and exposes the caller as `$request->user()` (a JwtUser).
 */
final readonly class VerifyJwt
{
    public function __construct(private Jwks $jwks) {}

    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $token = $request->bearerToken();
        if ($token === null) {
            abort(401, 'Missing bearer token.');
        }

        $claims = $this->decode($token);

        $subject = $claims->sub ?? null;
        if (($claims->iss ?? null) !== config()->string('shopwise-auth.issuer') || ! is_string($subject)) {
            abort(401, 'Invalid token claims.');
        }

        $tokenRoles = array_values(array_filter((array) ($claims->roles ?? []), is_string(...)));
        $email = $claims->email ?? null;
        $user = new JwtUser(id: $subject, roles: $tokenRoles, email: is_string($email) ? $email : null);

        foreach ($roles as $role) {
            if (! $user->hasRole($role)) {
                abort(403, "Requires role '{$role}'.");
            }
        }

        $request->setUserResolver(fn (): JwtUser => $user);

        /** @var Response */
        return $next($request);
    }

    private function decode(string $token): \stdClass
    {
        JWT::$leeway = config()->integer('shopwise-auth.leeway_seconds', 30);

        try {
            return JWT::decode($token, $this->jwks->keys());
        } catch (\UnexpectedValueException $e) {
            // Unknown "kid" usually means identity rotated its keys: refresh once.
            if (! str_contains($e->getMessage(), 'kid')) {
                abort(401, 'Invalid token.');
            }
        } catch (Throwable) {
            abort(401, 'Invalid token.');
        }

        try {
            return JWT::decode($token, $this->jwks->keys(refresh: true));
        } catch (Throwable) {
            abort(401, 'Invalid token.');
        }
    }
}
