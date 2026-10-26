<?php

declare(strict_types=1);

// mateusjunges/laravel-kafka only publishes its config, it never merges it; the kit ships it so every
// service gets the same defaults without a published copy. Keys match the package's config/kafka.php.
return [
    'brokers' => env('KAFKA_BROKERS', 'localhost:9092'),

    'securityProtocol' => env('KAFKA_SECURITY_PROTOCOL', 'PLAINTEXT'),

    'sasl' => [
        'mechanisms' => env('KAFKA_MECHANISMS', 'PLAINTEXT'),
        'username' => env('KAFKA_USERNAME'),
        'password' => env('KAFKA_PASSWORD'),
    ],

    'consumer_group_id' => env('KAFKA_CONSUMER_GROUP_ID', env('SERVICE_NAME', 'unknown')),

    'consumer_timeout_ms' => env('KAFKA_CONSUMER_DEFAULT_TIMEOUT', 2000),

    // A new consumer group starts from the beginning: consumers are idempotent, missing events is worse.
    'offset_reset' => env('KAFKA_OFFSET_RESET', 'earliest'),

    // ConsumeCommand commits manually after the handler ran.
    'auto_commit' => env('KAFKA_AUTO_COMMIT', false),

    'sleep_on_error' => env('KAFKA_ERROR_SLEEP', 5),

    'partition' => env('KAFKA_PARTITION', 0),

    'compression' => env('KAFKA_COMPRESSION_TYPE', 'none'),

    'debug' => env('KAFKA_DEBUG', false),

    'flush_retry_sleep_in_ms' => 100,

    'flush_retries' => 10,

    'flush_timeout_in_ms' => 1000,

    'cache_driver' => env('KAFKA_CACHE_DRIVER', env('CACHE_STORE', 'database')),

    'message_id_key' => env('MESSAGE_ID_KEY', 'laravel-kafka::message-id'),
];
