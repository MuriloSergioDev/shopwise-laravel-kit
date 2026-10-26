<?php

declare(strict_types=1);

return [
    // Producing service name; becomes the envelope "source" and the Kafka consumer group.
    'source' => env('SERVICE_NAME', env('APP_NAME', 'unknown')),

    'consumer_group' => env('KAFKA_CONSUMER_GROUP_ID', env('SERVICE_NAME', 'unknown')),

    // Event type => MessageHandler class. Services override this file.
    'handlers' => [],

    'max_attempts' => 3,

    'relay_batch_size' => 100,
];
