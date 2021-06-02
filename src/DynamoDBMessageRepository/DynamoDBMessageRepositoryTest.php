<?php

namespace EventSauce\MessageRepository\DynamoDBMessageRepository;

use AsyncAws\DynamoDb\DynamoDbClient;
use EventSauce\EventSourcing\AggregateRootId;
use EventSauce\EventSourcing\Header;
use EventSauce\EventSourcing\Message;
use EventSauce\EventSourcing\Serialization\ConstructingMessageSerializer;
use EventSauce\MessageRepository\TestTooling\MessageRepositoryTestCase;
use Ramsey\Uuid\Uuid;

class DynamoDBMessageRepositoryTest extends MessageRepositoryTestCase
{
    private DynamoDbClient $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = $this->client();

        if ($this->tableAlreadyExists($this->tableName)){
            return;
        }

        $this->client->createTable(
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
        $this->client->tableExists(['TableName' => $this->tableName])->wait();
    }

    private function client(): DynamoDbClient
    {
        $config = [
            'region' => 'us-west-2',
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

        if ($this->tableAlreadyExists($this->tableName)) {
            $client->deleteTable(['TableName' => $this->tableName]);
        }
        $client->tableNotExists(['TableName' => $this->tableName])->wait();
    }

    protected function aggregateRootId(): AggregateRootId
    {
        return DummyAggregateRootId::generate();
    }

    protected function eventId(): string
    {
        return Uuid::uuid4()->toString();
    }

    private function tableAlreadyExists(string $tableName): bool {
        $tables = $this->client->listTables();

        foreach ($tables->getIterator() as $table) {
            if ($table === $tableName) {
                return true;
            }
        }

        return false;
    }

    /**
     * @test
     */
    public function inserting_and_retrieving_messages_for_dynamodb(): void
    {
        $repository = $this->messageRepository();
        $messages = [];
        for ($i = 0; $i < 31; $i++) {
            $messages[] = $this->createMessage($i)->withHeader(
                Header::AGGREGATE_ROOT_VERSION,
                $i
            );
        }

        $repository->persist(...$messages);
        /** @var list<Message> $messages */
        $messages = iterator_to_array($repository->retrieveAll($this->aggregateRootId));

        self::assertCount(31, $messages);
    }
}
