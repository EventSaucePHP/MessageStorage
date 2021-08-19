<?php

namespace EventSauce\MessageRepository\DoctrineMessageRepository;

use EventSauce\EventSourcing\Serialization\ConstructingMessageSerializer;
use EventSauce\MessageRepository\DoctrineMessageRepository\TableSchema\DefaultTableSchema;
use EventSauce\MessageRepository\DoctrineMessageRepository\TableSchema\LegacyTableSchema;
use EventSauce\MessageRepository\DoctrineMessageRepository\UuidEncoder\BinaryUuidEncoder;
use EventSauce\MessageRepository\DoctrineMessageRepository\UuidEncoder\StringUuidEncoder;

class LegacyDoctrineUuidV4MessageRepositoryTest extends DoctrineUuidV4MessageRepositoryTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->tableName = 'legacy_domain_messages_uuid';
    }

    protected function messageRepository(): DoctrineUuidV4MessageRepository
    {
        return new DoctrineUuidV4MessageRepository(
            connection: $this->connection,
            tableName: $this->tableName,
            serializer: new ConstructingMessageSerializer(),
            tableSchema: new LegacyTableSchema(),
            uuidEncoder: new StringUuidEncoder(),
        );
    }
}
