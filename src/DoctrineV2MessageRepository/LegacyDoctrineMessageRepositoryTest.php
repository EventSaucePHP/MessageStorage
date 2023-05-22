<?php

namespace EventSauce\MessageRepository\DoctrineV2MessageRepository;

use EventSauce\EventSourcing\MessageRepository;
use EventSauce\EventSourcing\Serialization\ConstructingMessageSerializer;
use EventSauce\IdEncoding\StringIdEncoder;
use EventSauce\MessageRepository\TableSchema\LegacyTableSchema;
use EventSauce\UuidEncoding\StringUuidEncoder;

/**
 * @group doctrine2
 */
class LegacyDoctrineMessageRepositoryTest extends DoctrineUuidV4MessageRepositoryTestCase
{
    protected string $tableName = 'legacy_domain_messages_uuid';

    protected function messageRepository(): MessageRepository
    {
        return new DoctrineMessageRepository(
            connection: $this->connection,
            tableName: $this->tableName,
            serializer: new MySQL8DateFormatting(new ConstructingMessageSerializer()),
            tableSchema: new LegacyTableSchema(),
            aggregateRootIdEncoder: new StringIdEncoder(),
        );
    }
}
