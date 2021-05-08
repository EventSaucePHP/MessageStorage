<?php

namespace EventSauce\DynamoDBMessageRepository;

use Aws\DynamoDb\DynamoDbClient;
use EventSauce\EventSourcing\DefaultHeadersDecorator;
use EventSauce\EventSourcing\Serialization\ConstructingMessageSerializer;
use EventSauce\EventSourcing\Time\Clock;
use EventSauce\EventSourcing\Time\TestClock;
use EventSauce\EventSourcing\UuidAggregateRootId;
use EventSauce\EventSourcing\Header;
use EventSauce\EventSourcing\Message;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

use function iterator_to_array;

class DynamoDbMessageRepositoryTest extends TestCase
{
    /**
     * @var DynamoDbMessageRepository
     */
    private $repository;
    /**
     * @var DefaultHeadersDecorator
     */
    private $decorator;
    /**
     * @var Clock
     */
    private $clock;

    protected function setUp()
    {
        parent::setUp();
        $client = $this->client();
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
                'TableName' => 'domain_messages',
                'ProvisionedThroughput' => [
                    'ReadCapacityUnits' => 10,
                    'WriteCapacityUnits' => 10
                ]
            ]
        );
        $serializer = new ConstructingMessageSerializer();
        $this->clock = new TestClock();
        $this->decorator = new DefaultHeadersDecorator(null, $this->clock);
        $this->repository = $this->messageRepository($client, $serializer, 'domain_messages');
    }

    /**
     * @test
     */
    public function it_works()
    {
        $aggregateRootId = UuidAggregateRootId::create();
        $this->repository->persist();
        $this->assertEmpty(iterator_to_array($this->repository->retrieveAll($aggregateRootId)));
        $eventId = Uuid::uuid4()->toString();
        $message = $this->decorator->decorate(new Message(new DummyEvent(), [
            Header::EVENT_ID          => $eventId,
            Header::AGGREGATE_ROOT_ID => $aggregateRootId->toString(),
            Header::AGGREGATE_ROOT_VERSION => 10,
        ]));
        $this->repository->persist($message);
        $generator = $this->repository->retrieveAll($aggregateRootId);
        $retrievedMessage = iterator_to_array($generator, false)[0];
        $this->assertEquals($message, $retrievedMessage);
        $this->assertEquals(10, $generator->getReturn());
    }

    /**
     * @test
     */
    public function persisting_events_without_event_ids()
    {
        $message = $this->decorator->decorate(new Message(
            new DummyEvent(),
            [Header::AGGREGATE_ROOT_ID => Uuid::uuid4()->toString()]
        ));
        $this->repository->persist($message);
        $persistedMessages = iterator_to_array($this->repository->retrieveEverything());
        $this->assertCount(1, $persistedMessages);
        $this->assertNotEquals($message, $persistedMessages[0]);
    }
    /**
     * @test
     */
    public function retrieving_messages_after_a_specific_version()
    {
        $aggregateRootId = UuidAggregateRootId::create();
        $messages = [];
        $messages[] = $this->decorator->decorate(new Message(new DummyEvent(), [
            Header::EVENT_ID          => Uuid::uuid4()->toString(),
            Header::AGGREGATE_ROOT_ID => $aggregateRootId->toString(),
            Header::AGGREGATE_ROOT_VERSION => 10,
        ]));
        $messages[] = $this->decorator->decorate(new Message(new DummyEvent(), [
            Header::EVENT_ID          => $lastEventId = Uuid::uuid4()->toString(),
            Header::AGGREGATE_ROOT_ID => $aggregateRootId->toString(),
            Header::AGGREGATE_ROOT_VERSION => 11,
        ]));
        $this->repository->persist(...$messages);
        $generator = $this->repository->retrieveAllAfterVersion($aggregateRootId, 10);
        /** @var Message[] $messages */
        $messages = iterator_to_array($generator);
        $this->assertEquals(11, $generator->getReturn());
        $this->assertCount(1, $messages);
        $this->assertEquals($lastEventId, $messages[0]->header(Header::EVENT_ID));
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

    private function messageRepository(
        DynamoDbClient $client,
        ConstructingMessageSerializer $serializer,
        string $tableName
    ): DynamoDbMessageRepository {
        return new DynamoDbMessageRepository($client, $serializer, $tableName);
    }

    protected function tearDown()
    {
        parent::tearDown();
        $client = $this->client();
        $client->deleteTable(['TableName' => 'domain_messages']);
    }
}
