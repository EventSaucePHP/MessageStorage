<?php
declare(strict_types=1);

namespace EventSauce\IdEncoding;

use EventSauce\EventSourcing\AggregateRootId;

interface IdEncoder
{
    public function encodeId(AggregateRootId|string $id): mixed;
}