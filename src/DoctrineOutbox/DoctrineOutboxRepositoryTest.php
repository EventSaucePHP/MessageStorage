<?php

namespace EventSauce\MessageOutbox\DoctrineOutbox;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use EventSauce\EventSourcing\Serialization\ConstructingMessageSerializer;
use EventSauce\MessageOutbox\TestTooling\OutboxRepositoryTestCase;

class DoctrineOutboxRepositoryTest extends OutboxRepositoryTestCase
{
    private Connection $connection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->connection = DriverManager::getConnection(
            [
                'dbname' => 'outbox_messages',
                'user' => 'username',
                'password' => 'password',
                'host' => '127.0.0.1',
                'driver' => 'pdo_mysql',
            ]
        );
        $this->connection->executeQuery('TRUNCATE TABLE `outbox_messages`');
    }

    protected function outboxMessageRepository(): DoctrineOutboxRepository
    {
        return new DoctrineOutboxRepository(
            $this->connection, 'outbox_messages', new ConstructingMessageSerializer(),
        );
    }
}
