<?php

namespace EventSauce\MessageOutbox;

use EventSauce\EventSourcing\Serialization\SerializablePayload;

class DummyEvent implements SerializablePayload
{
    public function __construct(public string $value = 'example')
    {
    }

    public function toPayload(): array
    {
        return ['value' => $this->value];
    }

    public static function fromPayload(array $payload): SerializablePayload
    {
        return new self($payload['value']);
    }
}
