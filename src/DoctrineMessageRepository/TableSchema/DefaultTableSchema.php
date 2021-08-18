<?php

declare(strict_types=1);

namespace EventSauce\MessageRepository\DoctrineMessageRepository\TableSchema;

use EventSauce\MessageRepository\DoctrineMessageRepository\TableSchema;

final class DefaultTableSchema implements TableSchema
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
        return 'version';
    }

    public function payloadColumn(): string
    {
        return 'payload';
    }

    public function additionalColumns(): array
    {
        return [];
    }
}
