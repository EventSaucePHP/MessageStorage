<?php

namespace EventSauce\MessageRepository\DoctrineV2MessageRepository;

use EventSauce\EventSourcing\Serialization\ConstructingMessageSerializer;
use EventSauce\MessageRepository\TableSchema\DefaultTableSchema;
use EventSauce\UuidEncoding\BinaryUuidEncoder;

/**
 * @group doctrine2
 */
class DefaultDoctrineUuidV4MessageRepositoryTest extends DoctrineUuidV4MessageRepositoryTestCase
{
    protected string $tableName = 'domain_messages_uuid';

    protected function messageRepository(): DoctrineUuidV4MessageRepository
    {
        return new DoctrineUuidV4MessageRepository(
            connection: $this->connection,
            tableName: $this->tableName,
            serializer: new ConstructingMessageSerializer(),
            tableSchema: new DefaultTableSchema(),
            uuidEncoder: new BinaryUuidEncoder(),
        );
    }
}
