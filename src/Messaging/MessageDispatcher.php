<?php

declare(strict_types=1);

namespace Shopwise\Kit\Messaging;

use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Idempotent, retrying delivery of one envelope to its mapped handler.
 * Kept free of Kafka so it can be tested directly with contract examples.
 */
final readonly class MessageDispatcher
{
    public function __construct(
        private Container $container,
        private Publisher $publisher,
    ) {}

    /**
     * @param  array<string, string>  $headers
     */
    public function dispatch(Envelope $envelope, string $topic, array $headers = [], int $backoffMs = 200): DispatchResult
    {
        $maxAttempts = config()->integer('messaging.max_attempts', 3);

        for ($attempt = 1; ; $attempt++) {
            try {
                return Tracing::consume($envelope, $topic, $headers, fn (): DispatchResult => $this->handleOnce($envelope));
            } catch (Throwable $e) {
                if ($attempt >= $maxAttempts) {
                    $this->deadLetter($envelope, $topic, $headers, $e);

                    return DispatchResult::DeadLettered;
                }

                Log::warning('Message handling failed, retrying', ['id' => $envelope->id, 'type' => $envelope->type, 'attempt' => $attempt, 'error' => $e->getMessage()]);
                usleep($backoffMs * 1000 * $attempt);
            }
        }
    }

    private function handleOnce(Envelope $envelope): DispatchResult
    {
        return DB::transaction(function () use ($envelope): DispatchResult {
            $inserted = DB::table('processed_messages')->insertOrIgnore([
                'id' => $envelope->id,
                'type' => $envelope->type,
                'processed_at' => now(),
            ]);

            if ($inserted === 0) {
                return DispatchResult::Duplicate;
            }

            /** @var array<string, class-string<MessageHandler>> $handlers */
            $handlers = config('messaging.handlers', []);
            $handlerClass = $handlers[$envelope->type] ?? null;

            if ($handlerClass === null) {
                Log::info('No handler mapped; message acknowledged', ['type' => $envelope->type]);

                return DispatchResult::Ignored;
            }

            /** @var MessageHandler $handler */
            $handler = $this->container->make($handlerClass);
            $handler->handle($envelope);

            return DispatchResult::Handled;
        });
    }

    /**
     * @param  array<string, string>  $headers
     */
    private function deadLetter(Envelope $envelope, string $topic, array $headers, Throwable $e): void
    {
        Log::error('Message sent to DLQ', ['id' => $envelope->id, 'type' => $envelope->type, 'error' => $e->getMessage()]);

        $this->publisher->publish($topic.'.dlq', $envelope->subject, $envelope->toArray(), $headers + [
            'x-error' => mb_substr($e->getMessage(), 0, 500),
            'x-consumer' => config()->string('messaging.consumer_group'),
        ]);
    }
}
