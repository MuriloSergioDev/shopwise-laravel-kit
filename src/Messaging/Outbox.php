<?php

declare(strict_types=1);

namespace Shopwise\Kit\Messaging;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use LogicException;

/**
 * The only way domain code publishes events: a row in the service's own DB,
 * written in the caller's transaction and relayed to Kafka by `shopwise:relay-outbox`.
 */
final class Outbox
{
    /**
     * @param  array<string, mixed>  $data  Exactly the event schema's `data` payload.
     */
    public static function record(string $type, string $subject, array $data, ?string $dataschema = null): Envelope
    {
        if (DB::transactionLevel() === 0) {
            throw new LogicException("Outbox::record('{$type}') must run inside DB::transaction so state and event commit together.");
        }

        $envelope = new Envelope(
            id: (string) Str::ulid(),
            type: $type,
            source: config()->string('messaging.source'),
            time: Carbon::now('UTC')->format('Y-m-d\TH:i:s.v\Z'),
            subject: $subject,
            data: $data,
            dataschema: $dataschema,
        );

        OutboxMessage::query()->forceCreate([
            'id' => $envelope->id,
            'type' => $type,
            'subject' => $subject,
            'envelope' => $envelope->toArray(),
            'headers' => Tracing::currentHeaders() + ['ce_type' => $type],
        ]);

        return $envelope;
    }
}
