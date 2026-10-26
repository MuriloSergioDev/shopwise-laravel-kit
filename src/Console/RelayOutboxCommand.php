<?php

declare(strict_types=1);

namespace Shopwise\Kit\Console;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Shopwise\Kit\Messaging\OutboxMessage;
use Shopwise\Kit\Messaging\Publisher;

#[Description('Publish unpublished outbox messages to Kafka (at-least-once, in id order).')]
#[Signature('shopwise:relay-outbox')]
final class RelayOutboxCommand extends Command
{
    public function handle(Publisher $publisher): int
    {
        $published = DB::transaction(function () use ($publisher): int {
            $messages = OutboxMessage::query()
                ->whereNull('published_at')
                ->orderBy('id')
                ->limit(config()->integer('messaging.relay_batch_size', 100))
                ->lock('for update skip locked')
                ->get();

            foreach ($messages as $message) {
                $publisher->publish($message->type, $message->subject, $message->envelope, $message->headers);
                $message->forceFill(['published_at' => now()])->save();
            }

            return $messages->count();
        });

        if ($published > 0) {
            $this->components->info("Relayed {$published} message(s).");
        }

        return self::SUCCESS;
    }
}
