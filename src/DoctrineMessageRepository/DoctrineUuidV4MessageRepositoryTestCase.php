<?php

namespace EventSauce\MessageRepository\DoctrineMessageRepository;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\ResultStatement;
use Doctrine\DBAL\DriverManager;
use EventSauce\EventSourcing\AggregateRootId;
use EventSauce\MessageRepository\TestTooling\MessageRepositoryTestCase;
use Ramsey\Uuid\Uuid;

use function class_exists;

abstract class DoctrineUuidV4MessageRepositoryTestCase extends MessageRepositoryTestCase
{
    protected Connection $connection;

    protected function setUp(): void
    {
        if (class_exists(ResultStatement::class)) {
            $this->markTestSkipped('Doctrine v2 installed');
        }

        parent::setUp();
        $host = getenv('EVENTSAUCE_TESTING_MYSQL_HOST') ?: '127.0.0.1';
        $port = getenv('EVENTSAUCE_TESTING_MYSQL_PORT') ?: '3306';
        $connection = DriverManager::getConnection(
            [
                'url' => "mysql://username:password@$host:$port/outbox_messages",
            ]
        );
        $this->connection = $connection;
        $this->connection->executeQuery('TRUNCATE TABLE `' . $this->tableName . '`');
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
