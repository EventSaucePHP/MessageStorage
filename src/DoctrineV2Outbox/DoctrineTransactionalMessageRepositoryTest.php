<?php

namespace EventSauce\MessageOutbox\DoctrineV2Outbox;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\ResultStatement;
use Doctrine\DBAL\DriverManager;
use EventSauce\EventSourcing\AggregateRootId;
use EventSauce\EventSourcing\MessageRepository;
use EventSauce\EventSourcing\Serialization\ConstructingMessageSerializer;
use EventSauce\MessageOutbox\OutboxRepository;
use EventSauce\MessageOutbox\TestTooling\TransactionalMessageRepositoryTestCase;
use EventSauce\MessageRepository\DoctrineV2MessageRepository\DoctrineUuidV4MessageRepository;
use EventSauce\MessageRepository\DoctrineV2MessageRepository\DummyAggregateRootId;

use function interface_exists;

/**
 * @group doctrine2
 */
class DoctrineTransactionalMessageRepositoryTest extends TransactionalMessageRepositoryTestCase
{
    private Connection $connection;

    protected function setUp(): void
    {
        if ( ! interface_exists(ResultStatement::class)) {
            $this->markTestSkipped('No doctrine v2 installed');
        }

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
