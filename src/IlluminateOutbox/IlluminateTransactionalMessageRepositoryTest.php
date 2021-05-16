<?php

namespace EventSauce\MessageOutbox\IlluminateOutbox;

use EventSauce\EventSourcing\AggregateRootId;
use EventSauce\EventSourcing\MessageRepository;
use EventSauce\EventSourcing\Serialization\ConstructingMessageSerializer;
use EventSauce\MessageOutbox\OutboxRepository;
use EventSauce\MessageOutbox\TestTooling\TransactionalMessageRepositoryTestCase;
use EventSauce\MessageRepository\IlluminateMessageRepository\DummyAggregateRootId;
use EventSauce\MessageRepository\IlluminateMessageRepository\IlluminateUuidV4MessageRepository;
use Illuminate\Database\Capsule\Manager;
use Illuminate\Database\ConnectionInterface;

class IlluminateTransactionalMessageRepositoryTest extends TransactionalMessageRepositoryTestCase
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
        $this->connection->table($this->repositoryTable)->truncate();
        $this->connection->table($this->outboxTable)->truncate();
    }

    protected function messageRepository(): MessageRepository
    {
        return new IlluminateUuidV4MessageRepository(
            $this->connection,
            $this->repositoryTable,
            new ConstructingMessageSerializer(),
        );
    }

    protected function outboxRepository(): OutboxRepository
    {
        return new IlluminateOutboxRepository(
            $this->connection,
            $this->outboxTable,
            new ConstructingMessageSerializer(),
        );
    }

    protected function transactionalRepository(): MessageRepository
    {
        return new IlluminateTransactionalMessageRepository(
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
