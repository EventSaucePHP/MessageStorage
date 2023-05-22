<?php

namespace EventSauce\MessageRepository\IlluminateMessageRepository;

use EventSauce\EventSourcing\MessageRepository;
use EventSauce\EventSourcing\Serialization\ConstructingMessageSerializer;
use EventSauce\EventSourcing\Serialization\MySQL8DateFormatting;
use EventSauce\MessageRepository\TableSchema\DefaultTableSchema;

/**
 * @group illuminate
 */
class DefaultIlluminateMessageRepositoryTest extends IlluminateUuidV4MessageRepositoryTestCase
{
    protected string $tableName = 'domain_messages_uuid';

    protected function messageRepository(): MessageRepository
    {
        return new IlluminateMessageRepository(
            connection: $this->connection,
            tableName: $this->tableName,
            serializer: new MySQL8DateFormatting(new ConstructingMessageSerializer()),
            tableSchema: new DefaultTableSchema(),
        );
    }
}
