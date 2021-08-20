<?php

declare(strict_types=1);

namespace EventSauce\MessageRepository\TableSchema;

use EventSauce\EventSourcing\Header;

/**
 * Table schema used prior to version 1.0
 *
 * @link https://github.com/EventSaucePHP/DoctrineMessageRepository
 */
final class LegacyTableSchema implements TableSchema
{
    public function eventIdColumn(): string
    {
        return 'event_id';
    }

    public function aggregateRootIdColumn(): string
    {
        return 'aggregate_root_id';
    }

    public function versionColumn(): string
    {
        return 'aggregate_root_version';
    }

    public function payloadColumn(): string
    {
        return 'payload';
    }

    public function additionalColumns(): array
    {
        return [
            'event_type' => Header::EVENT_TYPE,
            'time_of_recording' => Header::TIME_OF_RECORDING,
        ];
    }
}
