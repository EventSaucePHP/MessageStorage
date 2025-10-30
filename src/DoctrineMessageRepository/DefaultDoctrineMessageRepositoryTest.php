<?php

namespace EventSauce\MessageRepository\DoctrineMessageRepository;

use EventSauce\IdEncoding\BinaryUuidIdEncoder;
use EventSauce\EventSourcing\Serialization\ConstructingMessageSerializer;
use EventSauce\MessageRepository\TableSchema\DefaultTableSchema;
use EventSauce\MessageRepository\TestTooling\BinaryUuidTestTrait;

class DefaultDoctrineMessageRepositoryTest extends DoctrineMessageRepositoryTestCase
{
    use BinaryUuidTestTrait;

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
