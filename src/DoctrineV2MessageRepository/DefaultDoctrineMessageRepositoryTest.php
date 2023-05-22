<?php

namespace EventSauce\MessageRepository\DoctrineV2MessageRepository;

use EventSauce\EventSourcing\MessageRepository;
use EventSauce\EventSourcing\Serialization\ConstructingMessageSerializer;
use EventSauce\MessageRepository\DoctrineV2MessageRepository\DoctrineUuidV4MessageRepositoryTestCase;
use EventSauce\MessageRepository\TableSchema\DefaultTableSchema;
use EventSauce\UuidEncoding\BinaryUuidEncoder;

/**
 * @group doctrine2
 */
class DefaultDoctrineMessageRepositoryTest extends DoctrineUuidV4MessageRepositoryTestCase
{
    protected string $tableName = 'domain_messages_uuid';

    protected function messageRepository(): MessageRepository
    {
        return new DoctrineMessageRepository(
            connection: $this->connection,
            tableName: $this->tableName,
            serializer: new ConstructingMessageSerializer(),
            tableSchema: new DefaultTableSchema(),
        );
    }
}
