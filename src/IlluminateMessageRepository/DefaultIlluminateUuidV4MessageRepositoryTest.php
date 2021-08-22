<?php

namespace EventSauce\MessageRepository\IlluminateMessageRepository;

use EventSauce\EventSourcing\MessageRepository;
use EventSauce\EventSourcing\Serialization\ConstructingMessageSerializer;
use EventSauce\EventSourcing\Serialization\MySQL8DateFormatting;
use EventSauce\MessageRepository\TableSchema\DefaultTableSchema;
use EventSauce\MessageRepository\TableSchema\LegacyTableSchema;
use EventSauce\UuidEncoding\BinaryUuidEncoder;
use EventSauce\UuidEncoding\StringUuidEncoder;

/**
 * @group illuminate
 */
class DefaultIlluminateUuidV4MessageRepositoryTest extends IlluminateUuidV4MessageRepositoryTestCase
{
    protected string $tableName = 'domain_messages_uuid';

    protected function messageRepository(): MessageRepository
    {
        return new IlluminateUuidV4MessageRepository(
            connection: $this->connection,
            tableName: $this->tableName,
            serializer: new MySQL8DateFormatting(new ConstructingMessageSerializer()),
            tableSchema: new DefaultTableSchema(),
            uuidEncoder: new BinaryUuidEncoder(),
        );
    }
}
