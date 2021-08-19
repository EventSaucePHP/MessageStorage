<?php

declare(strict_types=1);

namespace EventSauce\MessageRepository\DoctrineMessageRepository;

use Ramsey\Uuid\UuidInterface;

interface UuidEncoder
{
    public function encode(UuidInterface $uuid): string;

    public function encodeString(string $uuid): string;
}
