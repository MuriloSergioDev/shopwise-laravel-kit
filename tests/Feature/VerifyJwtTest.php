<?php

declare(strict_types=1);

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Shopwise\Kit\Auth\KeySource;

beforeEach(function (): void {
    $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
    openssl_pkey_export($key, $this->privateKey);
    $details = openssl_pkey_get_details($key);
    $b64 = fn (string $v): string => rtrim(strtr(base64_encode($v), '+/', '-_'), '=');

    Http::fake(['*/jwks.json' => Http::response(['keys' => [[
        'kty' => 'RSA', 'alg' => 'RS256', 'use' => 'sig', 'kid' => 'k1',
        'n' => $b64($details['rsa']['n']), 'e' => $b64($details['rsa']['e']),
    ]]])]);

    $this->token = fn (array $claims = []): string => JWT::encode(
        $claims + ['iss' => 'shopwise-identity', 'sub' => 'cust-1', 'roles' => ['customer'], 'exp' => time() + 300],
        $this->privateKey, 'RS256', 'k1',
    );

    Route::middleware('auth.jwt')->get('/me', fn (): array => ['id' => request()->user()->id]);
    Route::middleware('auth.jwt:admin')->get('/admin', fn (): array => ['ok' => true]);
});

it('exposes the verified caller as the request user', function (): void {
    $this->getJson('/me', ['Authorization' => 'Bearer '.($this->token)()])
        ->assertOk()->assertExactJson(['id' => 'cust-1']);
});

it('rejects missing, expired, foreign-issuer and garbage tokens', function (?string $token): void {
    $this->getJson('/me', $token ? ['Authorization' => "Bearer {$token}"] : [])->assertUnauthorized();
})->with([
    'missing' => null,
    'garbage' => 'not.a.jwt',
    'expired' => fn () => ($this->token)(['exp' => time() - 3600]),
    'foreign issuer' => fn () => ($this->token)(['iss' => 'someone-else']),
]);

it('verifies against a bound KeySource without fetching JWKS', function (): void {
    $public = openssl_pkey_get_details(openssl_pkey_get_private($this->privateKey))['key'];
    $this->app->instance(KeySource::class, new readonly class($public) implements KeySource
    {
        public function __construct(private string $public) {}

        public function keys(bool $refresh = false): array
        {
            return ['k1' => new Key($this->public, 'RS256')];
        }
    });

    $this->getJson('/me', ['Authorization' => 'Bearer '.($this->token)()])->assertOk();
    Http::assertNothingSent();
});

it('enforces required roles', function (): void {
    $this->getJson('/admin', ['Authorization' => 'Bearer '.($this->token)()])->assertForbidden();
    $this->getJson('/admin', ['Authorization' => 'Bearer '.($this->token)(['roles' => ['admin']])])->assertOk();
});
