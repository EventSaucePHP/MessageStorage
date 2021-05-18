<?php

namespace EventSauce\MessageRepository\DynamoDBMessageRepository;

use Aws\DynamoDb\DynamoDbClient;
use EventSauce\EventSourcing\AggregateRootId;
use EventSauce\EventSourcing\Serialization\ConstructingMessageSerializer;
use EventSauce\MessageRepository\TestTooling\MessageRepositoryTestCase;
use Ramsey\Uuid\Uuid;

class DynamoDBMessageRepositoryTest extends MessageRepositoryTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $client = $this->client();

        $tables = $client->listTables();
        if (in_array($this->tableName, $tables->get('TableNames'), true)){
            return;
        }

        $client->createTable(
            [
                'AttributeDefinitions' => [
                    ['AttributeName' => 'aggregateRootId', 'AttributeType' => 'S'],
                    ['AttributeName' => 'aggregateRootVersion', 'AttributeType' => 'N'],
                ],
                'KeySchema' => [
                    ['AttributeName' => 'aggregateRootId', 'KeyType' => 'HASH'],
                    ['AttributeName' => 'aggregateRootVersion', 'KeyType' => 'RANGE']
                ],
                'TableName' => $this->tableName,
                'ProvisionedThroughput' => [
                    'ReadCapacityUnits' => 10,
                    'WriteCapacityUnits' => 10
                ]
            ]
        );
        $client->waitUntil('TableExists', ['TableName' => $this->tableName]);
    }

    private function client(): DynamoDbClient
    {
        $config = [
            'region' => 'us-west-2',
            'version' => 'latest',
            'endpoint' => 'http://localhost:8000'
        ];

        return new DynamoDbClient($config);
    }

    protected function messageRepository(): DynamoDBMessageRepository {
        return new DynamoDBMessageRepository(
            $this->client(),
            new ConstructingMessageSerializer(),
            $this->tableName
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $client = $this->client();
        $tables = $client->listTables();
        if (!in_array($this->tableName, $tables->get('TableNames'), true)){
            return;
        }
        $client->deleteTable(['TableName' => $this->tableName]);
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
