<?php

namespace EventSauce\MessageOutbox\IlluminateMessageOutbox;

use EventSauce\EventSourcing\Serialization\ConstructingMessageSerializer;
use EventSauce\MessageOutbox\OutboxMessageRepository;
use EventSauce\MessageOutbox\TestTooling\OutboxMessageRepositoryTestCase;
use Illuminate\Database\Capsule\Manager;
use Illuminate\Database\ConnectionInterface;

class IlluminateOutboxMessageRepositoryTest extends OutboxMessageRepositoryTestCase
{
    private ConnectionInterface $connection;

    protected function setUp(): void
    {
        parent::setUp();

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
        $this->connection->table('outbox_messages')->truncate();
    }

    protected function outboxMessageRepository(): OutboxMessageRepository
    {
        return new IlluminateOutboxMessageRepository(
            $this->connection,
            'outbox_messages',
            new ConstructingMessageSerializer(),
        );
    }
}
