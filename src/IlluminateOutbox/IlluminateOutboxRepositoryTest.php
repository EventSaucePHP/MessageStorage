<?php

namespace EventSauce\MessageOutbox\IlluminateOutbox;

use EventSauce\EventSourcing\Serialization\ConstructingMessageSerializer;
use EventSauce\MessageOutbox\TestTooling\OutboxRepositoryTestCase;
use Illuminate\Database\Capsule\Manager;
use Illuminate\Database\ConnectionInterface;

class IlluminateOutboxRepositoryTest extends OutboxRepositoryTestCase
{
    private ConnectionInterface $connection;

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
        $this->connection->table('outbox_messages')->truncate();
    }

    protected function outboxMessageRepository(): IlluminateOutboxRepository
    {
        return new IlluminateOutboxRepository(
            $this->connection, 'outbox_messages', new ConstructingMessageSerializer(),
        );
    }
}
