<?php

declare(strict_types=1);

namespace EventSauce\MessageRepository\TableSchema;

use PHPUnit\Framework\TestCase;

class DefaultTableSchemaTest extends TestCase
{
    private TableSchema $tableSchema;

    protected function setUp(): void
    {
        $this->tableSchema = new DefaultTableSchema();
    }

    public function testItHoldsTableAndColumnNames(): void
    {
        self::assertSame('event_id', $this->tableSchema->eventIdColumn());
        self::assertSame('aggregate_root_id', $this->tableSchema->aggregateRootIdColumn());
        self::assertSame('version', $this->tableSchema->versionColumn());
        self::assertSame('payload', $this->tableSchema->payloadColumn());
        self::assertCount(0, $this->tableSchema->additionalColumns());
    }
}
