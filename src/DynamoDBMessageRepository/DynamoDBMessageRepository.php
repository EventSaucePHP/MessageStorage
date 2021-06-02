<?php

namespace EventSauce\MessageRepository\DynamoDBMessageRepository;

use AsyncAws\DynamoDb\DynamoDbClient;
use AsyncAws\DynamoDb\ValueObject\AttributeValue;
use EventSauce\EventSourcing\AggregateRootId;
use EventSauce\EventSourcing\Message;
use EventSauce\EventSourcing\MessageRepository;
use EventSauce\EventSourcing\Serialization\MessageSerializer;
use EventSauce\EventSourcing\Header;
use EventSauce\EventSourcing\UnableToPersistMessages;
use EventSauce\EventSourcing\UnableToRetrieveMessages;
use Ramsey\Uuid\Uuid;
use Generator;
use Throwable;

class DynamoDBMessageRepository implements MessageRepository
{
    protected DynamoDbClient $client;
    protected MessageSerializer $serializer;
    protected string $tableName;

    public function __construct(DynamoDbClient $client, MessageSerializer $serializer, string $tableName)
    {
        $this->client = $client;
        $this->serializer = $serializer;
        $this->tableName = $tableName;
    }

    public function persist(Message ...$messages): void
    {
        if (count($messages) === 0) {
            return;
        }

        $items = [];
        foreach ($messages as $message) {
            $payload = $this->serializer->serializeMessage($message);
            $event = $payload['headers'][Header::EVENT_ID] = $payload['headers'][Header::EVENT_ID] ?? Uuid::uuid4()->toString();
            $aggregateRootId = $payload['headers'][Header::AGGREGATE_ROOT_ID] ?? null;
            $eventType = $payload['headers'][Header::EVENT_TYPE] ?? null;
            $aggregateRootVersion = $payload['headers'][Header::AGGREGATE_ROOT_VERSION] ?? 0;
            $timeOfRecording = $payload['headers'][Header::TIME_OF_RECORDING];

            $item = [
                'event' => ['S' => $event],
                'aggregateRootId' => ['S' => $aggregateRootId],
                'eventType' => ['S' => $eventType],
                'aggregateRootVersion' => ['N' => $aggregateRootVersion],
                'timeOfRecording' => ['S' => $timeOfRecording],
                'payload' => ['S' => json_encode($payload)]
            ];

            $items[] = [
                'PutRequest' => [
                    'Item' => $item
                ]
            ];
        }

        try {
            $this->batchWriteItems($items);
        } catch (Throwable $exception) {
            throw UnableToPersistMessages::dueTo('', $exception);
        }

    }

    private function batchWriteItems(array $items): void
    {
        $batches = array_chunk($items, 25,true);
        foreach ($batches as $batchItems) {
            $this->client->batchWriteItem(['RequestItems' => [$this->tableName => $batchItems]]);
        }
    }

    public function retrieveAll(AggregateRootId $id): Generator
    {
        $query = [
            'TableName' => $this->tableName,
            'ConsistentRead' => true,
            'KeyConditionExpression' => 'aggregateRootId = :aggregateRootId',
            'ExpressionAttributeValues' => [
                ':aggregateRootId' => ['S' => $id->toString()],
            ]
        ];

        try {
            $this->client->query($query)->info();
            return $this->yieldMessagesForResult($this->client->query($query));
        } catch (\Throwable $exception) {
            throw UnableToRetrieveMessages::dueTo('', $exception);
        }
    }

    /** @psalm-return Generator<Message> */
    public function retrieveAllAfterVersion(AggregateRootId $id, int $aggregateRootVersion): Generator
    {
        $query = [
            'TableName' => $this->tableName,
            'ConsistentRead' => true,
            'KeyConditionExpression'
                => 'aggregateRootId = :aggregateRootId AND aggregateRootVersion > :aggregateRootVersion',
            'ExpressionAttributeValues' => [
                ':aggregateRootId' => ['S' => $id->toString()],
                ':aggregateRootVersion' => ['N' => $aggregateRootVersion]
            ]
        ];

        try {
            $this->client->query($query)->info();
            return $this->yieldMessagesForResult($this->client->query($query));
        } catch (Throwable $exception) {
            throw UnableToRetrieveMessages::dueTo('', $exception);
        }
    }

    /**
     * @psalm-return Generator<Message>
     */
    private function yieldMessagesForResult(iterable $result): Generator
    {
        /** @var array<AttributeValue> $item */
        foreach ($result as $item) {
            if(isset($item['payload'])) {
                $payload = $item['payload']->getS();
                $message = $this->serializer->unserializePayload(json_decode($payload ?: '[]', true));

                yield $message;
            }
        }

        return isset($message)
            ? $message->header(Header::AGGREGATE_ROOT_VERSION) ?: 0
            : 0;
    }
}
