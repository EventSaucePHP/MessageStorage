<?php

namespace EventSauce\MessageRepository\DoctrineMessageRepository;

use EventSauce\EventSourcing\Serialization\ConstructingMessageSerializer;
use EventSauce\MessageRepository\TableSchema\LegacyTableSchema;
use EventSauce\UuidEncoding\StringUuidEncoder;

class LegacyDoctrineUuidV4MessageRepositoryTest extends DoctrineUuidV4MessageRepositoryTestCase
{
    protected string $tableName = 'legacy_domain_messages_uuid';

    protected function messageRepository(): DoctrineUuidV4MessageRepository
    {
        return new DoctrineUuidV4MessageRepository(
            connection: $this->connection,
            tableName: $this->tableName,
            serializer: new MySQL8DateFormatting(new ConstructingMessageSerializer()),
            tableSchema: new LegacyTableSchema(),
            uuidEncoder: new StringUuidEncoder(),
        );
    }
}
