<?php

declare(strict_types=1);

namespace EventSauce\MessageRepository\DoctrineMessageRepository\UuidEncoder;

use EventSauce\MessageRepository\DoctrineMessageRepository\UuidEncoder;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

final class StringUuidEncoder implements UuidEncoder
{
    public function encode(UuidInterface $uuid): string
    {
        return $uuid->toString();
    }

    public function encodeString(string $uuid): string
    {
        return $this->encode(Uuid::fromString($uuid));
    }
}

