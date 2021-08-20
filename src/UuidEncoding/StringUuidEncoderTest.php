<?php

declare(strict_types=1);

namespace EventSauce\UuidEncoding;

use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class StringUuidEncoderTest extends TestCase
{
    private StringUuidEncoder $encoder;

    protected function setUp(): void
    {
        $this->encoder = new StringUuidEncoder();
    }

    public function testItSerializesUuidToString(): void
    {
        $uuid = Uuid::uuid4();

        self::assertSame($uuid->toString(), $this->encoder->encodeUuid($uuid));
        self::assertSame($uuid->toString(), $this->encoder->encodeString($uuid->toString()));
    }
}
