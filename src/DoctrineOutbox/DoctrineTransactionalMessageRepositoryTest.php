<?php

namespace EventSauce\MessageOutbox\DoctrineOutbox;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use EventSauce\DoctrineMessageRepository\DoctrineUuidV4MessageRepository;
use EventSauce\DoctrineMessageRepository\DummyAggregateRootId;
use EventSauce\EventSourcing\AggregateRootId;
use EventSauce\EventSourcing\MessageRepository;
use EventSauce\EventSourcing\Serialization\ConstructingMessageSerializer;
use EventSauce\MessageOutbox\OutboxRepository;
use EventSauce\MessageOutbox\TestTooling\TransactionalMessageRepositoryTestCase;

class DoctrineTransactionalMessageRepositoryTest extends TransactionalMessageRepositoryTestCase
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
        $this->connection->executeQuery('TRUNCATE TABLE `'.$this->repositoryTable.'`');
        $this->connection->executeQuery('TRUNCATE TABLE `'.$this->outboxTable.'`');
    }

    protected function messageRepository(): MessageRepository
    {
        return new DoctrineUuidV4MessageRepository(
            $this->connection,
            $this->repositoryTable,
            new ConstructingMessageSerializer(),
        );
    }

    protected function outboxRepository(): OutboxRepository
    {
        return new DoctrineOutboxRepository(
            $this->connection,
            $this->outboxTable,
            new ConstructingMessageSerializer(),
        );
    }

    protected function transactionalRepository(): MessageRepository
    {
        return new DoctrineTransactionalMessageRepository(
            $this->connection,
            $this->messageRepository(),
            $this->outboxRepository(),
        );
    }

    protected function aggregateRootId(): AggregateRootId
    {
        return DummyAggregateRootId::generate();
    }
}
