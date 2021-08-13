<?php

namespace EventSauce\MessageRepository\DoctrineV2MessageRepository;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\ResultStatement;
use Doctrine\DBAL\DriverManager;
use EventSauce\EventSourcing\AggregateRootId;
use EventSauce\MessageRepository\TestTooling\MessageRepositoryTestCase;
use Ramsey\Uuid\Uuid;

use function class_exists;
use function interface_exists;
use function var_dump;

abstract class DoctrineUuidV4MessageRepositoryTestCase extends MessageRepositoryTestCase
{
    protected Connection $connection;

    protected function setUp(): void
    {
        if ( ! interface_exists(ResultStatement::class)) {
            $this->markTestSkipped('No Doctrine v2 installed');
        }

        parent::setUp();
        $connection = DriverManager::getConnection(
            [
                'dbname' => 'outbox_messages',
                'user' => 'username',
                'password' => 'password',
                'host' => getenv('EVENTSAUCE_TESTING_MYSQL_HOST') ?: '127.0.0.1',
                'port' => getenv('EVENTSAUCE_TESTING_MYSQL_PORT') ?: '3306',
                'driver' => 'pdo_mysql',
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
