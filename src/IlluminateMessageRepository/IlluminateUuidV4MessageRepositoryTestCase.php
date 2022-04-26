<?php

namespace EventSauce\MessageRepository\IlluminateMessageRepository;

use EventSauce\EventSourcing\AggregateRootId;
use EventSauce\MessageRepository\TestTooling\MessageRepositoryTestCase;
use Illuminate\Database\Capsule\Manager;
use Illuminate\Database\Connection;
use Ramsey\Uuid\Uuid;

/**
 * @group illuminate
 */
abstract class IlluminateUuidV4MessageRepositoryTestCase extends MessageRepositoryTestCase
{
    protected Connection $connection;

    protected function setUp(): void
    {
        parent::setUp();

        $manager = new Manager;
        $manager->addConnection(
            [
                'driver' => 'mysql',
                'host' => getenv('EVENTSAUCE_TESTING_MYSQL_HOST') ?: '127.0.0.1',
                'port' => getenv('EVENTSAUCE_TESTING_MYSQL_PORT') ?: '3306',
                'database' => 'outbox_messages',
                'username' => 'username',
                'password' => 'password',
                'charset' => 'utf8',
                'collation' => 'utf8_unicode_ci',
            ]
        );

        $this->connection = $manager->getConnection();
        $this->connection->table($this->tableName)->truncate();
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
