<?php
declare(strict_types=1);

namespace EventSauce\IdEncoding;

use EventSauce\EventSourcing\AggregateRootId;
use Ramsey\Uuid\Uuid;

class BinaryUuidIdEncoder implements IdEncoder
{
    public function encodeId(AggregateRootId|string $id): string
    {
        return Uuid::fromString($id instanceof AggregateRootId ? $id->toString() : $id)->getBytes();
    }
}