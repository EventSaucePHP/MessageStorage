<?php

namespace EventSauce\MessageRepository\IlluminateMessageRepository;

use EventSauce\EventSourcing\AggregateRootId;
use Ramsey\Uuid\Uuid;

final class DummyAggregateRootId implements AggregateRootId
{
    private function __construct(private string $uuid)
    {
    }

    public function toString(): string
    {
        return $this->uuid;
    }

    public static function fromString(string $aggregateRootId): AggregateRootId
    {
        return new self($aggregateRootId);
    }

    public static function generate(): DummyAggregateRootId
    {
        return new self(Uuid::uuid4()->toString());
    }
}
