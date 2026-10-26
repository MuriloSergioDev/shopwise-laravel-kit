<?php

declare(strict_types=1);

namespace Shopwise\Kit\Messaging;

use InvalidArgumentException;

/**
 * CloudEvents-style envelope for every Shopwise Kafka message (ADR-0002).
 */
final readonly class Envelope
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public string $id,
        public string $type,
        public string $source,
        public string $time,
        public string $subject,
        public array $data,
        public ?string $dataschema = null,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        if (! is_array($payload['data'] ?? null)) {
            throw new InvalidArgumentException("Envelope field 'data' must be an object.");
        }

        /** @var array<string, mixed> $data */
        $data = $payload['data'];
        $dataschema = $payload['dataschema'] ?? null;

        return new self(
            id: self::string($payload, 'id'),
            type: self::string($payload, 'type'),
            source: self::string($payload, 'source'),
            time: self::string($payload, 'time'),
            subject: self::string($payload, 'subject'),
            data: $data,
            dataschema: is_string($dataschema) ? $dataschema : null,
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private static function string(array $payload, string $field): string
    {
        $value = $payload[$field] ?? null;

        if (! is_string($value) || $value === '') {
            throw new InvalidArgumentException("Envelope field '{$field}' must be a non-empty string.");
        }

        return $value;
    }

    public static function fromJson(string $json): self
    {
        /** @var array<string, mixed> $payload */
        $payload = json_decode($json, true, flags: JSON_THROW_ON_ERROR);

        return self::fromArray($payload);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'id' => $this->id,
            'type' => $this->type,
            'source' => $this->source,
            'time' => $this->time,
            'subject' => $this->subject,
            'dataschema' => $this->dataschema,
            'data' => $this->data,
        ], fn (mixed $value): bool => $value !== null);
    }
}
