<?php

namespace EventSauce\MessageOutbox\DoctrineOutbox;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\ResultStatement;
use Doctrine\DBAL\DriverManager;
use EventSauce\EventSourcing\AggregateRootId;
use EventSauce\EventSourcing\MessageRepository;
use EventSauce\EventSourcing\Serialization\ConstructingMessageSerializer;
use EventSauce\MessageOutbox\OutboxRepository;
use EventSauce\MessageOutbox\TestTooling\TransactionalMessageRepositoryTestCase;
use EventSauce\MessageRepository\DoctrineMessageRepository\DoctrineUuidV4MessageRepository;
use EventSauce\MessageRepository\DoctrineMessageRepository\DummyAggregateRootId;

use function getenv;
use function interface_exists;

class DoctrineTransactionalMessageRepositoryTest extends TransactionalMessageRepositoryTestCase
{
    private Connection $connection;

    protected function setUp(): void
    {
        if (interface_exists(ResultStatement::class)) {
            $this->markTestSkipped('Doctrine v2 installed');
        }

        parent::setUp();

        $host = getenv('EVENTSAUCE_TESTING_MYSQL_HOST') ?: '127.0.0.1';
        $port = getenv('EVENTSAUCE_TESTING_MYSQL_PORT') ?: '3306';

        $this->connection = DriverManager::getConnection(
            [
                'url' => "mysql://username:password@$host:$port/outbox_messages",
            ]
        );

        $this->connection->executeQuery('TRUNCATE TABLE '.$this->repositoryTable);
        $this->connection->executeQuery('TRUNCATE TABLE '.$this->outboxTable);
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
