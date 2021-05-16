<?php

namespace EventSauce\MessageRepository\IlluminateMessageRepository;

use EventSauce\EventSourcing\AggregateRootId;
use EventSauce\EventSourcing\MessageRepository;
use EventSauce\EventSourcing\Serialization\ConstructingMessageSerializer;
use EventSauce\MessageRepository\TestTooling\MessageRepositoryTestCase;
use Illuminate\Database\Capsule\Manager;
use Illuminate\Database\Connection;
use Ramsey\Uuid\Uuid;

class IlluminateUuidV4MessageRepositoryTest extends MessageRepositoryTestCase
{
    private Connection $connection;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tableName = 'domain_messages_uuid';

        $manager = new Manager;
        $manager->addConnection(
            [
                'driver' => 'mysql',
                'host' => '127.0.0.1',
                'port' => '3306',
                'database' => 'outbox_messages',
                'username' => 'username',
                'password' => 'password',
                'charset' => 'utf8',
                'collation' => 'utf8_unicode_ci',
            ]
        );

        $this->connection = $manager->getConnection();
        $this->connection->table('domain_messages_uuid')->truncate();
    }

    protected function messageRepository(): MessageRepository
    {
        return new IlluminateUuidV4MessageRepository(
            $this->connection, $this->tableName, new ConstructingMessageSerializer(),
        );
    }

    protected function aggregateRootId(): AggregateRootId
    {
        return DummyAggregateRootId::generate();
    }

    protected function eventId(): string
    {
        return Uuid::uuid4()->toString();
    }
}
