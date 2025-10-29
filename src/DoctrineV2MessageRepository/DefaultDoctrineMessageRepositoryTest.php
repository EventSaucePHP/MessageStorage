<?php

namespace EventSauce\MessageRepository\DoctrineV2MessageRepository;

use EventSauce\EventSourcing\MessageRepository;
use EventSauce\EventSourcing\Serialization\ConstructingMessageSerializer;
use EventSauce\MessageRepository\DoctrineV2MessageRepository\DoctrineMessageRepositoryTestCase;
use EventSauce\MessageRepository\TableSchema\DefaultTableSchema;

/**
 * @group doctrine2
 */
class DefaultDoctrineMessageRepositoryTest extends DoctrineMessageRepositoryTestCase
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
