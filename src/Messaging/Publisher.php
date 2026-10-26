<?php

declare(strict_types=1);

namespace Shopwise\Kit\Messaging;

interface Publisher
{
    /**
     * @param  array<string, mixed>  $body
     * @param  array<string, string>  $headers
     */
    public function publish(string $topic, string $key, array $body, array $headers): void;
}
