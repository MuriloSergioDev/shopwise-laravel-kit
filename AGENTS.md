# shopwise-laravel-kit

Composer package (`shopwise/laravel-kit`) every Shopwise Laravel service requires. It owns the cross-cutting
runtime so services only contain domain code. Auto-discovered via `KitServiceProvider`.

## What it provides
| Piece | Used by services as |
|---|---|
| `Messaging\Outbox::record()` + `outbox_messages` migration + `shopwise:relay-outbox` (scheduled every second) | the only way to publish events |
| `shopwise:consume <topics...>` + `MessageDispatcher` + `processed_messages` | idempotent consumers; handlers mapped in the service's `config/messaging.php` |
| `Messaging\MessageHandler` | interface for event handlers |
| `Messaging\Tracing` | W3C trace context over Kafka headers |
| `Auth\VerifyJwt` (alias `auth.jwt`, `auth.jwt:<role>`) + `Auth\JwtUser` | JWT from identity, verified via JWKS; `$request->user()` |
| `Money\MoneyCast` | `<x>_amount` + `<x>_currency` columns ↔ `Brick\Money\Money` |
| `Testing\FakePublisher` | assert published messages in service tests |

Behavioural contract: `shopwise-platform/claude-plugin/skills/outbox-and-idempotency/SKILL.md` and ADR-0002. Changing semantics here changes every service — that is a `feat!:` with a migration note.

## Working here
- No host PHP needed: `docker run --rm -v "$PWD:/app" -w /app shopwise-php:base composer check` (image built from `shopwise-service-template`, target `base`).
- Tests use Orchestra Testbench + in-memory SQLite with `DatabaseMigrations` (not `RefreshDatabase`, which would hide the outbox transaction guard).
- Larastan runs at `level: max`; narrow `mixed` with `config()->string()/integer()` and `is_*` checks instead of casts.

## Checks
`composer check` = Pint --test, Rector --dry-run, Larastan, Pest --parallel.
