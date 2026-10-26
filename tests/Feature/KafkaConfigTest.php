<?php

declare(strict_types=1);

use Junges\Kafka\Consumers\Builder;
use Junges\Kafka\Facades\Kafka;

it('provides the laravel-kafka config so consumers can be built without a published copy', function (): void {
    expect(config('kafka.auto_commit'))->toBeFalse()
        ->and(config('kafka.offset_reset'))->toBe('earliest')
        // The builder's constructor reads kafka.* into typed properties; a missing config throws here.
        ->and(Kafka::consumer(['test.topic']))->toBeInstanceOf(Builder::class);
});
