<?php

namespace EventSauce\MessageRepository\DoctrineV2MessageRepository;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\ResultStatement;
use Doctrine\DBAL\DriverManager;
use EventSauce\EventSourcing\AggregateRootId;
use EventSauce\EventSourcing\Serialization\ConstructingMessageSerializer;
use EventSauce\MessageRepository\TestTooling\MessageRepositoryTestCase;
use Ramsey\Uuid\Uuid;

use function interface_exists;

/**
 * @group doctrine2
 */
class DoctrineUuidV4MessageRepositoryTest extends MessageRepositoryTestCase
{
    private Connection $connection;

    protected function setUp(): void
    {
        if ( ! interface_exists(ResultStatement::class)) {
            die("WHAT");
            $this->markTestSkipped('No doctrine v2 installed');
        }

        parent::setUp();
        $connection = DriverManager::getConnection(
            [
                'dbname' => 'outbox_messages',
                'user' => 'username',
                'password' => 'password',
                'host' => '127.0.0.1',
                'driver' => 'pdo_mysql',
            ]
        );
        $this->connection = $connection;
        $this->connection->executeQuery('TRUNCATE TABLE `domain_messages_uuid`');
    }

    protected function messageRepository(): DoctrineUuidV4MessageRepository
    {
        return new DoctrineUuidV4MessageRepository(
            $this->connection, $this->tableName, new ConstructingMessageSerializer(),
        );
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
