<?php

namespace EventSauce\MessageRepository\IlluminateMessageRepository;

use EventSauce\EventSourcing\MessageRepository;
use EventSauce\EventSourcing\Serialization\ConstructingMessageSerializer;
use EventSauce\EventSourcing\Serialization\MySQL8DateFormatting;
use EventSauce\IdEncoding\StringIdEncoder;
use EventSauce\MessageRepository\TableSchema\LegacyTableSchema;

/**
 * @group illuminate
 */
class LegacyIlluminateMessageRepositoryTest extends IlluminateMessageRepositoryTestCase
{
    protected string $tableName = 'legacy_domain_messages_uuid';

    protected function messageRepository(): MessageRepository
    {
        return new IlluminateMessageRepository(
            connection: $this->connection,
            tableName: $this->tableName,
            serializer: new MySQL8DateFormatting(new ConstructingMessageSerializer()),
            tableSchema: new LegacyTableSchema(),
            aggregateRootIdEncoder: new StringIdEncoder(),
        );
    }
}
