<?php

declare(strict_types=1);

namespace EventSauce\MessageRepository\DoctrineMessageRepository\UuidEncoder;

use EventSauce\MessageRepository\DoctrineMessageRepository\UuidEncoder\BinaryUuidEncoder;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class BinaryUuidEncoderTest extends TestCase
{
    private BinaryUuidEncoder $encoder;

    protected function setUp(): void
    {
        $this->encoder = new BinaryUuidEncoder();
    }

    public function testItSerializesUuidToString(): void
    {
        $uuid = Uuid::uuid4();

        self::assertSame($uuid->getBytes(), $this->encoder->encodeUuid($uuid));
        self::assertSame($uuid->getBytes(), $this->encoder->encodeString($uuid->toString()));
    }
}
