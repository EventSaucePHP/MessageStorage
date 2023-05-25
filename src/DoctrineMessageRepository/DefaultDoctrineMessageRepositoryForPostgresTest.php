<?php

namespace EventSauce\MessageRepository\DoctrineMessageRepository;

use EventSauce\IdEncoding\StringIdEncoder;
use EventSauce\EventSourcing\MessageRepository;
use EventSauce\EventSourcing\Serialization\ConstructingMessageSerializer;
use EventSauce\MessageRepository\TableSchema\DefaultTableSchema;
use function getenv;

class DefaultDoctrineMessageRepositoryForPostgresTest extends DoctrineMessageRepositoryTestCase
{
    protected string $tableName = 'domain_messages_uuid';

    protected function messageRepository(): MessageRepository
    {
        return new DoctrineMessageRepository(
            connection: $this->connection,
            tableName: $this->tableName,
            serializer: new ConstructingMessageSerializer(),
            tableSchema: new DefaultTableSchema(),
            aggregateRootIdEncoder: new StringIdEncoder(),
        );
    }

    protected function formatDsn(): string
    {
        $host = getenv('EVENTSAUCE_TESTING_PGSQL_HOST') ?: '127.0.0.1';
        $port = getenv('EVENTSAUCE_TESTING_PGSQL_PORT') ?: '5432';
        $dsn = "pgsql://username:password@$host:$port/outbox_messages";

        return $dsn;
    }
}
