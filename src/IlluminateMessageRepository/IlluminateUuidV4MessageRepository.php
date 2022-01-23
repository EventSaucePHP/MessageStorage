<?php

namespace EventSauce\MessageRepository\IlluminateMessageRepository;

use EventSauce\EventSourcing\AggregateRootId;
use EventSauce\EventSourcing\Header;
use EventSauce\EventSourcing\Message;
use EventSauce\EventSourcing\MessageRepository;
use EventSauce\EventSourcing\Serialization\MessageSerializer;
use EventSauce\EventSourcing\UnableToPersistMessages;
use EventSauce\EventSourcing\UnableToRetrieveMessages;
use EventSauce\MessageRepository\TableSchema\DefaultTableSchema;
use EventSauce\MessageRepository\TableSchema\TableSchema;
use EventSauce\UuidEncoding\BinaryUuidEncoder;
use EventSauce\UuidEncoding\UuidEncoder;
use Generator;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Collection;
use Ramsey\Uuid\Uuid;
use Throwable;

use function count;

class IlluminateUuidV4MessageRepository implements MessageRepository
{
    protected TableSchema $tableSchema;
    protected UuidEncoder $uuidEncoder;

    public function __construct(
        protected ConnectionInterface $connection,
        protected string $tableName,
        protected MessageSerializer $serializer,
        protected int $jsonEncodeOptions = 0,
        ?TableSchema $tableSchema = null,
        ?UuidEncoder $uuidEncoder = null,
    ) {
        $this->tableSchema = $tableSchema ?? new DefaultTableSchema();
        $this->uuidEncoder = $uuidEncoder ?? new BinaryUuidEncoder();
    }

    public function persist(Message ...$messages): void
    {
        if (count($messages) === 0) {
            return;
        }

        $values = [];
        $versionColumn = $this->tableSchema->versionColumn();
        $eventIdColumn = $this->tableSchema->eventIdColumn();
        $payloadColumn = $this->tableSchema->payloadColumn();
        $aggregateRootIdColumn = $this->tableSchema->aggregateRootIdColumn();
        $additionalColumns = $this->tableSchema->additionalColumns();

        foreach ($messages as $message) {
            $parameters = [];
            $payload = $this->serializer->serializeMessage($message);
            $parameters[$versionColumn] = $payload['headers'][Header::AGGREGATE_ROOT_VERSION] ?? 0;
            $payload['headers'][Header::EVENT_ID] = $payload['headers'][Header::EVENT_ID] ?? Uuid::uuid4()->toString();
            $parameters[$eventIdColumn] = $this->uuidEncoder->encodeString($payload['headers'][Header::EVENT_ID]);
            $parameters[$payloadColumn] = json_encode($payload, $this->jsonEncodeOptions);
            $parameters[$aggregateRootIdColumn] = $this->uuidEncoder->encodeString($payload['headers'][Header::AGGREGATE_ROOT_ID]);

            foreach ($additionalColumns as $column => $header) {
                $parameters[$column] = $payload['headers'][$header];
            }

            $values[] = $parameters;
        }

        try {
            $this->connection->table($this->tableName)->insert($values);
        } catch (Throwable $exception) {
            throw UnableToPersistMessages::dueTo('', $exception);
        }
    }

    public function retrieveAll(AggregateRootId $id): Generator
    {
        $builder = $this->connection->table($this->tableName)
            ->where($this->tableSchema->aggregateRootIdColumn(), $this->uuidEncoder->encodeString($id->toString()))
            ->orderBy($this->tableSchema->versionColumn(), 'ASC');

        try {
            return $this->yieldMessagesForResult($builder->get(['payload']));
        } catch (Throwable $exception) {
            throw UnableToRetrieveMessages::dueTo('', $exception);
        }
    }

    /** @psalm-return Generator<Message> */
    public function retrieveAllAfterVersion(AggregateRootId $id, int $aggregateRootVersion): Generator
    {
        $versionColumn = $this->tableSchema->versionColumn();
        $builder = $this->connection->table($this->tableName)
            ->where($this->tableSchema->aggregateRootIdColumn(), $this->uuidEncoder->encodeString($id->toString()))
            ->where($versionColumn, '>', $aggregateRootVersion)
            ->orderBy($versionColumn, 'ASC');

        try {
            return $this->yieldMessagesForResult($builder->get(['payload']));
        } catch (Throwable $exception) {
            throw UnableToRetrieveMessages::dueTo('', $exception);
        }
    }

    /**
     * @psalm-return Generator<Message>
     */
    protected function yieldMessagesForResult(Collection $result): Generator
    {
        foreach ($result as $row) {
            $message = $this->serializer->unserializePayload(json_decode($row->payload, true));
            yield $message;
        }

        return isset($message) ? $message->header(Header::AGGREGATE_ROOT_VERSION) ?: 0 : 0;
    }
}
