<?php

namespace EventSauce\MessageRepository\DoctrineMessageRepository;

use EventSauce\IdEncoding\BinaryUuidIdEncoder;
use EventSauce\EventSourcing\Serialization\ConstructingMessageSerializer;
use EventSauce\MessageRepository\TableSchema\DefaultTableSchema;

class DefaultDoctrineMessageRepositoryTest extends DoctrineMessageRepositoryTestCase
{
    protected string $tableName = 'domain_messages_uuid';

    protected function messageRepository(): DoctrineMessageRepository
    {
        return new DoctrineMessageRepository(
            connection: $this->connection,
            tableName: $this->tableName,
            serializer: new ConstructingMessageSerializer(),
            tableSchema: new DefaultTableSchema(),
            aggregateRootIdEncoder: new BinaryUuidIdEncoder(),
        );
    }
}
