<?php

declare(strict_types=1);

namespace EventSauce\MessageRepository\DoctrineMessageRepository\TableSchema;

use EventSauce\EventSourcing\Header;
use EventSauce\MessageRepository\DoctrineMessageRepository\TableSchema;
use EventSauce\MessageRepository\DoctrineMessageRepository\TableSchema\LegacyTableSchema;
use PHPUnit\Framework\TestCase;

class LegacyTableSchemaTest extends TestCase
{
    private TableSchema $tableSchema;

    protected function setUp(): void
    {
        $this->tableSchema = new LegacyTableSchema();
    }

    public function testItHoldsTableAndColumnNames(): void
    {
        self::assertSame('event_id', $this->tableSchema->eventIdColumn());
        self::assertSame('aggregate_root_id', $this->tableSchema->aggregateRootIdColumn());
        self::assertSame('aggregate_root_version', $this->tableSchema->versionColumn());
        self::assertSame('payload', $this->tableSchema->payloadColumn());
        self::assertCount(2, $this->tableSchema->additionalColumns());
        self::assertSame(Header::TIME_OF_RECORDING, $this->tableSchema->additionalColumns()['time_of_recording']);
        self::assertSame(Header::EVENT_TYPE, $this->tableSchema->additionalColumns()['event_type']);
    }
}
