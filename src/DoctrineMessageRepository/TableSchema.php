<?php

declare(strict_types=1);

namespace EventSauce\MessageRepository\DoctrineMessageRepository;

interface TableSchema
{
    public function eventIdColumn(): string;

    public function aggregateRootIdColumn(): string;

    public function versionColumn(): string;

    public function payloadColumn(): string;

    /**
     * Map of column name to Header value
     *
     * @return array<string,string>
     */
    public function additionalColumns(): array;
}
