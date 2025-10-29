<?php

namespace EventSauce\MessageRepository\DoctrineMessageRepository;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\ResultStatement;
use Doctrine\DBAL\DriverManager;
use EventSauce\EventSourcing\AggregateRootId;
use EventSauce\MessageOutbox\TestTooling\DoctrineConnectionTrait;
use EventSauce\MessageRepository\TestTooling\MessageRepositoryTestCase;
use Ramsey\Uuid\Uuid;

use function class_exists;
use function getenv;
use function str_starts_with;

abstract class DoctrineMessageRepositoryTestCase extends MessageRepositoryTestCase
{
    use DoctrineConnectionTrait;

    protected Connection $connection;

    protected function setUp(): void
    {
        if (class_exists(ResultStatement::class)) {
            $this->markTestSkipped('Doctrine v2 installed');
        }

        parent::setUp();
        $connection = DriverManager::getConnection($this->getConnectionParams());
        $this->connection = $connection;
        $this->truncateTable();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        if (str_contains($this->formatDsn(), 'pgsql')) {
            // can only check this for mysql
            return;
        }

        $warning = $this->connection->executeQuery('SHOW WARNINGS')->fetchNumeric();
        if ($warning !== false && count($warning) > 0) {
            if (str_contains($warning[2], 'Base table or view not found') || str_contains($warning[2], "doesn't exist")) {
                // shortcut for tests
                return;
            }
            self::fail(sprintf(
                'Warnings issued durings tests, these can potentially result in data loss: [%d] %s',
                $warning[1],
                $warning[2],
            ));
        }
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

    protected function aggregateRootId(): AggregateRootId
    {
        return DummyAggregateRootId::generate();
    }

    protected function eventId(): string
    {
        return Uuid::uuid4()->toString();
    }

}
