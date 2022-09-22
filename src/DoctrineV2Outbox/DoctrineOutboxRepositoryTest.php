<?php

namespace EventSauce\MessageOutbox\DoctrineV2Outbox;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\ResultStatement;
use Doctrine\DBAL\DriverManager;
use EventSauce\EventSourcing\Serialization\ConstructingMessageSerializer;
use EventSauce\MessageOutbox\TestTooling\OutboxRepositoryTestCase;

use function interface_exists;

/**
 * @group doctrine2
 */
class DoctrineOutboxRepositoryTest extends OutboxRepositoryTestCase
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
                'host' => getenv('EVENTSAUCE_TESTING_MYSQL_HOST') ?: '127.0.0.1',
                'port' => getenv('EVENTSAUCE_TESTING_MYSQL_PORT') ?: '3306',
                'driver' => 'pdo_mysql',
            ]
        );
        $this->connection->executeQuery('TRUNCATE TABLE outbox_messages');
    }

    protected function outboxMessageRepository(): DoctrineOutboxRepository
    {
        return new DoctrineOutboxRepository(
            $this->connection, 'outbox_messages', new ConstructingMessageSerializer(),
        );
    }
}
