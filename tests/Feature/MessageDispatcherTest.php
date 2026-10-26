<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Shopwise\Kit\Messaging\DispatchResult;
use Shopwise\Kit\Messaging\Envelope;
use Shopwise\Kit\Messaging\MessageDispatcher;
use Shopwise\Kit\Messaging\MessageHandler;
use Shopwise\Kit\Messaging\Outbox;

final class CountingHandler implements MessageHandler
{
    public static int $calls = 0;

    public function handle(Envelope $envelope): void
    {
        self::$calls++;
        Outbox::record('test.followed-up', $envelope->subject, []);
    }
}

final class FailingHandler implements MessageHandler
{
    public function handle(Envelope $envelope): void
    {
        throw new RuntimeException('boom');
    }
}

function envelope(string $type = 'test.happened'): Envelope
{
    return new Envelope(id: 'msg-1', type: $type, source: 'other', time: '2026-10-26T12:00:00Z', subject: 'agg-1', data: ['x' => 1]);
}

beforeEach(function (): void {
    CountingHandler::$calls = 0;
    $this->dispatcher = app(MessageDispatcher::class);
});

it('handles a message exactly once even when redelivered', function (): void {
    config(['messaging.handlers' => ['test.happened' => CountingHandler::class]]);

    expect($this->dispatcher->dispatch(envelope(), 'test.happened'))->toBe(DispatchResult::Handled)
        ->and($this->dispatcher->dispatch(envelope(), 'test.happened'))->toBe(DispatchResult::Duplicate)
        ->and(CountingHandler::$calls)->toBe(1)
        ->and(DB::table('outbox_messages')->where('type', 'test.followed-up')->count())->toBe(1);
});

it('acknowledges messages without a mapped handler', function (): void {
    expect($this->dispatcher->dispatch(envelope('test.unknown'), 'test.unknown'))->toBe(DispatchResult::Ignored);
});

it('dead-letters after max attempts and leaves no trace of the failed attempt', function (): void {
    config(['messaging.handlers' => ['test.happened' => FailingHandler::class], 'messaging.max_attempts' => 3]);

    $result = $this->dispatcher->dispatch(envelope(), 'test.happened', ['traceparent' => 'tp'], backoffMs: 0);

    expect($result)->toBe(DispatchResult::DeadLettered)
        ->and(DB::table('processed_messages')->count())->toBe(0);
    $this->publisher->assertPublished('test.happened.dlq');
    expect($this->publisher->on('test.happened.dlq')[0]['headers'])->toMatchArray(['traceparent' => 'tp', 'x-error' => 'boom']);
});

it('decodes envelopes and rejects incomplete ones', function (): void {
    $json = json_encode(envelope()->toArray(), JSON_THROW_ON_ERROR);

    expect(Envelope::fromJson($json))->toEqual(envelope())
        ->and(fn (): Envelope => Envelope::fromArray(['id' => 'x']))->toThrow(InvalidArgumentException::class);
});
