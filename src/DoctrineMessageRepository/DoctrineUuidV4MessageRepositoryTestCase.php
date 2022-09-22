<?php

namespace EventSauce\MessageRepository\DoctrineMessageRepository;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\ResultStatement;
use Doctrine\DBAL\DriverManager;
use EventSauce\EventSourcing\AggregateRootId;
use EventSauce\MessageRepository\TestTooling\MessageRepositoryTestCase;
use Ramsey\Uuid\Uuid;

use function class_exists;
use function getenv;
use function str_starts_with;

abstract class DoctrineUuidV4MessageRepositoryTestCase extends MessageRepositoryTestCase
{
    protected Connection $connection;

    protected function setUp(): void
    {
        if (class_exists(ResultStatement::class)) {
            $this->markTestSkipped('Doctrine v2 installed');
        }

        parent::setUp();
        $connection = DriverManager::getConnection(['url' => $this->formatDsn()]);
        $this->connection = $connection;
        $this->truncateTable();
    }

    protected function formatDsn(): string
    {
        $host = getenv('EVENTSAUCE_TESTING_MYSQL_HOST') ?: '127.0.0.1';
        $port = getenv('EVENTSAUCE_TESTING_MYSQL_PORT') ?: '3306';
        $dsn = "mysql://username:password@$host:$port/outbox_messages";

        return $dsn;
    }

    protected function truncateTable(): void
    {
        if (str_starts_with($this->formatDsn(), 'mysql')) {
            $this->connection->executeQuery('TRUNCATE TABLE ' . $this->tableName);
        } else {
            $this->connection->executeQuery('TRUNCATE TABLE ' . $this->tableName . ' RESTART IDENTITY CASCADE');
        }
    }

    abstract protected function messageRepository(): DoctrineUuidV4MessageRepository;

    protected function aggregateRootId(): AggregateRootId
    {
        return DummyAggregateRootId::generate();
    }

    protected function eventId(): string
    {
        return Uuid::uuid4()->toString();
    }

}
