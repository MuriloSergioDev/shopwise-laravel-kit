<?php

declare(strict_types=1);

namespace Shopwise\Kit\Console;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Junges\Kafka\Contracts\ConsumerMessage;
use Junges\Kafka\Contracts\MessageConsumer;
use Junges\Kafka\Facades\Kafka;
use Shopwise\Kit\Messaging\Envelope;
use Shopwise\Kit\Messaging\MessageDispatcher;
use Throwable;

#[Description('Consume Kafka topics and dispatch envelopes to config(messaging.handlers), idempotently.')]
#[Signature('shopwise:consume {topics* : Topics to subscribe to}')]
final class ConsumeCommand extends Command
{
    public function handle(MessageDispatcher $dispatcher): int
    {
        /** @var list<string> $topics */
        $topics = $this->argument('topics');
        $this->components->info('Consuming '.implode(', ', $topics).' as '.config()->string('messaging.consumer_group'));

        Kafka::consumer($topics)
            ->withConsumerGroupId(config()->string('messaging.consumer_group'))
            ->withManualCommit()
            ->withHandler(function (ConsumerMessage $message, MessageConsumer $consumer) use ($dispatcher, $topics): void {
                try {
                    $body = $message->getBody();
                    /** @var array<string, mixed> $decoded */
                    $decoded = is_string($body) ? json_decode($body, true, flags: JSON_THROW_ON_ERROR) : (array) $body;
                    $envelope = Envelope::fromArray($decoded);
                } catch (Throwable $e) {
                    // Undecodable message: nothing can ever process it, so log and move on.
                    Log::error('Discarding malformed message', ['topic' => $message->getTopicName(), 'offset' => $message->getOffset(), 'error' => $e->getMessage()]);
                    $consumer->commit($message);

                    return;
                }

                $headers = [];
                foreach ($message->getHeaders() ?? [] as $name => $value) {
                    if (is_scalar($value)) {
                        $headers[(string) $name] = (string) $value;
                    }
                }

                $result = $dispatcher->dispatch($envelope, $message->getTopicName() ?? $topics[0], $headers);
                $this->line("{$envelope->type} {$envelope->id} → {$result->name}");
                $consumer->commit($message);
            })
            ->build()
            ->consume();

        return self::SUCCESS;
    }
}
