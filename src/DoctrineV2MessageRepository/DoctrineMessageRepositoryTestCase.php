<?php

namespace EventSauce\MessageRepository\DoctrineV2MessageRepository;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\ResultStatement;
use Doctrine\DBAL\DriverManager;
use EventSauce\EventSourcing\AggregateRootId;
use EventSauce\MessageRepository\TestTooling\MessageRepositoryTestCase;
use Ramsey\Uuid\Uuid;

use function getenv;
use function interface_exists;

abstract class DoctrineMessageRepositoryTestCase extends MessageRepositoryTestCase
{
    protected Connection $connection;

    protected function setUp(): void
    {
        if ( ! interface_exists(ResultStatement::class)) {
            $this->markTestSkipped('No Doctrine v2 installed');
        }

        parent::setUp();
        $dsn = $this->formatDsn();

        $connection = DriverManager::getConnection(['url' => $dsn]);
        $this->connection = $connection;
        $this->connection->executeQuery('TRUNCATE TABLE ' . $this->tableName);
    }

    protected function aggregateRootId(): AggregateRootId
    {
        return DummyAggregateRootId::generate();
    }

    protected function eventId(): string
    {
        return Uuid::uuid4()->toString();
    }

    protected function formatDsn(): string
    {
        $host = getenv('EVENTSAUCE_TESTING_MYSQL_HOST') ?: '127.0.0.1';
        $port = getenv('EVENTSAUCE_TESTING_MYSQL_PORT') ?: '3306';
        $dsn = "mysql://username:password@$host:$port/outbox_messages";

        return $dsn;
    }
}
