<?php

declare(strict_types=1);

namespace Shopwise\Kit\Messaging;

use Junges\Kafka\Facades\Kafka;
use Junges\Kafka\Message\Message;

final class KafkaPublisher implements Publisher
{
    public function publish(string $topic, string $key, array $body, array $headers): void
    {
        Kafka::publish()
            ->onTopic($topic)
            ->withMessage(new Message(headers: $headers, body: $body, key: $key))
            ->send();
    }
}
