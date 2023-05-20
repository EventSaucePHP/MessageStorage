<?php
declare(strict_types=1);

namespace EventSauce\IdEncoding;

use EventSauce\EventSourcing\AggregateRootId;

class StringIdEncoder implements IdEncoder
{
    public function encodeId(AggregateRootId|string $id): mixed
    {
        return $id instanceof AggregateRootId
            ? $id->toString()
            : $id;
    }
}