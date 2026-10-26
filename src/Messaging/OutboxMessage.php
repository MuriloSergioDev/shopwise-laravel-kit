<?php

declare(strict_types=1);

namespace Shopwise\Kit\Messaging;

use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $type
 * @property string $subject
 * @property array<string, mixed> $envelope
 * @property array<string, string> $headers
 * @property Carbon|null $published_at
 */
#[Guarded(['id'])]
#[Table(name: 'outbox_messages')]
final class OutboxMessage extends Model
{
    use HasUlids;

    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'envelope' => 'array',
            'headers' => 'array',
            'published_at' => 'datetime',
        ];
    }
}
