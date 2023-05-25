<?php

namespace EventSauce\MessageRepository\DoctrineMessageRepository;

use EventSauce\EventSourcing\Serialization\ConstructingMessageSerializer;
use EventSauce\MessageRepository\TableSchema\DefaultTableSchema;
use EventSauce\UuidEncoding\BinaryUuidEncoder;

use EventSauce\UuidEncoding\StringUuidEncoder;

use function getenv;

class DefaultDoctrineUuidV4MessageRepositoryForPostgresTest extends DoctrineMessageRepositoryTestCase
{
    protected string $tableName = 'domain_messages_uuid';

    protected function messageRepository(): DoctrineUuidV4MessageRepository
    {
        return new DoctrineUuidV4MessageRepository(
            connection: $this->connection,
            tableName: $this->tableName,
            serializer: new ConstructingMessageSerializer(),
            tableSchema: new DefaultTableSchema(),
            uuidEncoder: new StringUuidEncoder(),
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
