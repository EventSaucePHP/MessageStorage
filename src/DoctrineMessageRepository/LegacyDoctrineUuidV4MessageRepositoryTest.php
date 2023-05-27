<?php

namespace EventSauce\MessageRepository\DoctrineMessageRepository;

use EventSauce\EventSourcing\Serialization\ConstructingMessageSerializer;
use EventSauce\MessageRepository\TableSchema\LegacyTableSchema;
use EventSauce\UuidEncoding\StringUuidEncoder;

use function getenv;

class LegacyDoctrineUuidV4MessageRepositoryTest extends DoctrineMessageRepositoryTestCase
{
    protected string $tableName = 'legacy_domain_messages_uuid';

    protected function messageRepository(): DoctrineUuidV4MessageRepository
    {
        return new DoctrineUuidV4MessageRepository(
            connection: $this->connection,
            tableName: $this->tableName,
            serializer: new MySQL8DateFormatting(new ConstructingMessageSerializer()),
            tableSchema: new LegacyTableSchema(),
            uuidEncoder: new StringUuidEncoder(),
        );
    }

    protected function formatDsn(): string
    {
        $host = getenv('EVENTSAUCE_TESTING_MYSQL_HOST') ?: '127.0.0.1';
        $port = getenv('EVENTSAUCE_TESTING_MYSQL_PORT') ?: '3306';

        return "mysql://username:password@$host:$port/outbox_messages";
    }
}
