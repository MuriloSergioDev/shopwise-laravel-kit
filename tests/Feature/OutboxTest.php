<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Shopwise\Kit\Messaging\Envelope;
use Shopwise\Kit\Messaging\Outbox;
use Shopwise\Kit\Messaging\OutboxMessage;

it('refuses to record outside a transaction', function (): void {
    Outbox::record('platform.pinged', 'abc', ['pingId' => 'abc']);
})->throws(LogicException::class, 'must run inside DB::transaction');

it('records an envelope with the service as source', function (): void {
    $envelope = DB::transaction(fn (): Envelope => Outbox::record('platform.pinged', 'abc', ['pingId' => 'abc'], 'platform/pinged.v1'));

    $row = OutboxMessage::query()->sole();
    expect($row->id)->toBe($envelope->id)
        ->and($row->envelope)->toMatchArray([
            'type' => 'platform.pinged',
            'source' => 'kit-test',
            'subject' => 'abc',
            'dataschema' => 'platform/pinged.v1',
            'data' => ['pingId' => 'abc'],
        ])
        ->and($row->headers['ce_type'])->toBe('platform.pinged')
        ->and($row->published_at)->toBeNull();
});

it('rolls the event back with the transaction', function (): void {
    try {
        DB::transaction(function (): void {
            Outbox::record('platform.pinged', 'abc', []);
            throw new RuntimeException('domain failure');
        });
    } catch (RuntimeException) {
    }

    expect(OutboxMessage::query()->count())->toBe(0);
});

it('relays unpublished messages once, keyed by subject', function (): void {
    DB::transaction(function (): void {
        Outbox::record('platform.pinged', 'a', ['n' => 1]);
        Outbox::record('platform.pinged', 'b', ['n' => 2]);
    });

    $this->artisan('shopwise:relay-outbox')->assertSuccessful();
    $this->artisan('shopwise:relay-outbox')->assertSuccessful();

    $this->publisher->assertPublished('platform.pinged', 2);
    expect(array_column($this->publisher->published, 'key'))->toBe(['a', 'b'])
        ->and(OutboxMessage::query()->whereNull('published_at')->count())->toBe(0);
});
