<?php

declare(strict_types=1);

namespace Shopwise\Kit\Testing;

use PHPUnit\Framework\Assert;
use Shopwise\Kit\Messaging\Publisher;

/**
 * In-memory Publisher for service tests: `$this->app->instance(Publisher::class, $fake = new FakePublisher)`.
 */
final class FakePublisher implements Publisher
{
    /** @var list<array{topic: string, key: string, body: array<string, mixed>, headers: array<string, string>}> */
    public array $published = [];

    public function publish(string $topic, string $key, array $body, array $headers): void
    {
        $this->published[] = ['topic' => $topic, 'key' => $key, 'body' => $body, 'headers' => $headers];
    }

    /**
     * @return list<array{topic: string, key: string, body: array<string, mixed>, headers: array<string, string>}>
     */
    public function on(string $topic): array
    {
        return array_values(array_filter($this->published, fn (array $m): bool => $m['topic'] === $topic));
    }

    public function assertPublished(string $topic, int $times = 1): void
    {
        Assert::assertCount($times, $this->on($topic), "Expected {$times} message(s) on {$topic}.");
    }
}
