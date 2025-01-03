<?php

namespace EventSauce\MessageOutbox;

use EventSauce\EventSourcing\Serialization\SerializablePayload;

/**
 * @codeCoverageIgnore
 */
final class DummyEvent implements SerializablePayload
{
    public function __construct(public string $value = 'example')
    {
    }

    /**
     * @return array<string, string>
     */
    public function toPayload(): array
    {
        return ['value' => $this->value];
    }

    /**
     * @param array<string, string> $payload
     */
    public static function fromPayload(array $payload): static
    {
        return new static($payload['value']);
    }
}
