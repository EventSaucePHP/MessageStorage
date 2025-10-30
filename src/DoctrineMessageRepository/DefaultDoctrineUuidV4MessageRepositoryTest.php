<?php

namespace EventSauce\MessageRepository\DoctrineMessageRepository;

use EventSauce\EventSourcing\Serialization\ConstructingMessageSerializer;
use EventSauce\MessageRepository\TableSchema\DefaultTableSchema;
use EventSauce\MessageRepository\TestTooling\BinaryUuidTestTrait;
use EventSauce\UuidEncoding\BinaryUuidEncoder;

class DefaultDoctrineUuidV4MessageRepositoryTest extends DoctrineMessageRepositoryTestCase
{
    use BinaryUuidTestTrait;

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
