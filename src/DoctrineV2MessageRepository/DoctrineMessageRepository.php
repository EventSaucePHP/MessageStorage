<?php

namespace EventSauce\MessageRepository\DoctrineV2MessageRepository;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\ResultStatement;
use Doctrine\DBAL\ForwardCompatibility\Result;
use Doctrine\DBAL\Query\QueryBuilder;
use EventSauce\EventSourcing\AggregateRootId;
use EventSauce\EventSourcing\Header;
use EventSauce\EventSourcing\Message;
use EventSauce\EventSourcing\MessageRepository;
use EventSauce\EventSourcing\OffsetCursor;
use EventSauce\EventSourcing\PaginationCursor;
use EventSauce\EventSourcing\Serialization\MessageSerializer;
use EventSauce\EventSourcing\UnableToPersistMessages;
use EventSauce\EventSourcing\UnableToRetrieveMessages;
use EventSauce\IdEncoding\BinaryUuidIdEncoder;
use EventSauce\IdEncoding\IdEncoder;
use EventSauce\MessageRepository\TableSchema\DefaultTableSchema;
use EventSauce\MessageRepository\TableSchema\TableSchema;
use Generator;
use LogicException;
use Ramsey\Uuid\Uuid;
use Throwable;
use Traversable;
use function array_keys;
use function array_map;
use function array_merge;
use function count;
use function get_class;
use function implode;
use function json_decode;
use function json_encode;
use function sprintf;

class DoctrineMessageRepository implements MessageRepository
{
    private TableSchema $tableSchema;
    private IdEncoder $aggregateIdEncoder;
    private IdEncoder $eventIdEncoder;

    public function __construct(
        private Connection $connection,
        private string $tableName,
        private MessageSerializer $serializer,
        private int $jsonEncodeOptions = 0,
        ?TableSchema $tableSchema = null,
        ?IdEncoder $aggregateRootIdEncoder = null,
        ?IdEncoder $eventIdEncoder = null,
    ) {
        $this->tableSchema = $tableSchema ?? new DefaultTableSchema();
        $this->aggregateIdEncoder = $aggregateRootIdEncoder ?? new BinaryUuidIdEncoder();
        $this->eventIdEncoder = $eventIdEncoder ?? $this->aggregateIdEncoder;
    }

    public function persist(Message ...$messages): void
    {
        if (count($messages) === 0) {
            return;
        }

        $insertColumns = [
            $this->tableSchema->eventIdColumn(),
            $this->tableSchema->aggregateRootIdColumn(),
            $this->tableSchema->versionColumn(),
            $this->tableSchema->payloadColumn(),
            ...array_keys($additionalColumns = $this->tableSchema->additionalColumns()),
        ];

        $insertValues = [];
        $insertParameters = [];

        foreach ($messages as $index => $message) {
            $payload = $this->serializer->serializeMessage($message);
            $payload['headers'][Header::EVENT_ID] ??= Uuid::uuid4()->toString();

            $messageParameters = [
                $this->indexParameter('event_id', $index) => $this->eventIdEncoder->encodeId($payload['headers'][Header::EVENT_ID]),
                $this->indexParameter('aggregate_root_id', $index) => $this->aggregateIdEncoder->encodeId($message->aggregateRootId()),
                $this->indexParameter('version', $index) => $payload['headers'][Header::AGGREGATE_ROOT_VERSION] ?? 0,
                $this->indexParameter('payload', $index) => json_encode($payload, $this->jsonEncodeOptions),
            ];

            foreach ($additionalColumns as $column => $header) {
                $messageParameters[$this->indexParameter($column, $index)] = $payload['headers'][$header];
            }

            // Creates a values line like: (:event_id_1, :aggregate_root_id_1, ...)
            $insertValues[] = implode(', ', $this->formatNamedParameters(array_keys($messageParameters)));

            // Flatten the message parameters into the query parameters
            $insertParameters = array_merge($insertParameters, $messageParameters);
        }

        $insertQuery = sprintf(
            "INSERT INTO %s (%s) VALUES\n(%s)",
            $this->tableName,
            implode(', ', $insertColumns),
            implode("),\n(", $insertValues),
        );

        try {
            $this->connection->executeStatement($insertQuery, $insertParameters);
        } catch (Throwable $exception) {
            throw UnableToPersistMessages::dueTo('', $exception);
        }
    }

    private function indexParameter(string $name, int $index): string
    {
        return $name . '_' . $index;
    }

    private function formatNamedParameters(array $parameters): array
    {
        return array_map(static fn (string $name) => ':' . $name, $parameters);
    }

    public function retrieveAll(AggregateRootId $id): Generator
    {
        $builder = $this->createQueryBuilder();
        $builder->where(sprintf('%s = :aggregate_root_id', $this->tableSchema->aggregateRootIdColumn()));
        $builder->setParameter('aggregate_root_id', $this->aggregateIdEncoder->encodeId($id));

        try {
            /** @var ResultStatement $resultStatement */
            $resultStatement = $builder->execute();

            return $this->yieldMessagesFromPayloads($resultStatement);
        } catch (Throwable $exception) {
            throw UnableToRetrieveMessages::dueTo('', $exception);
        }
    }

    /**
     * @psalm-return Generator<Message>
     */
    public function retrieveAllAfterVersion(AggregateRootId $id, int $aggregateRootVersion): Generator
    {
        $builder = $this->createQueryBuilder();
        $builder->where(sprintf('%s = :aggregate_root_id', $this->tableSchema->aggregateRootIdColumn()));
        $builder->andWhere(sprintf('%s > :version', $this->tableSchema->versionColumn()));
        $builder->setParameter('aggregate_root_id', $this->aggregateIdEncoder->encodeId($id));
        $builder->setParameter('version', $aggregateRootVersion);

        try {
            /** @var ResultStatement $resultStatement */
            $resultStatement = $builder->execute();

            return $this->yieldMessagesFromPayloads($resultStatement);
        } catch (Throwable $exception) {
            throw UnableToRetrieveMessages::dueTo('', $exception);
        }
    }

    private function createQueryBuilder(): QueryBuilder
    {
        $builder = $this->connection->createQueryBuilder();
        $builder->select($this->tableSchema->payloadColumn());
        $builder->from($this->tableName);
        $builder->orderBy($this->tableSchema->versionColumn(), 'ASC');

        return $builder;
    }

    /**
     * @psalm-return Generator<Message>
     */
    private function yieldMessagesFromPayloads(Traversable $payloads): Generator
    {
        foreach ($payloads as $payload) {
            yield $message = $this->serializer->unserializePayload(json_decode($payload['payload'], true));
        }

        return isset($message)
            ? $message->header(Header::AGGREGATE_ROOT_VERSION) ?: 0
            : 0;
    }

    public function paginate(PaginationCursor $cursor): Generator
    {
        if ( ! $cursor instanceof OffsetCursor) {
            throw new LogicException(sprintf('Wrong cursor type used, expected %s, received %s', OffsetCursor::class, get_class($cursor)));
        }

        $numberOfMessages = 0;
        $builder = $this->connection->createQueryBuilder();
        $builder->select($this->tableSchema->payloadColumn());
        $builder->from($this->tableName);
        $incrementalIdColumn = $this->tableSchema->incrementalIdColumn();
        $builder->orderBy($incrementalIdColumn, 'ASC');
        $builder->setMaxResults($cursor->limit());
        $builder->where($incrementalIdColumn . ' > :id');
        $builder->setParameter('id', $cursor->offset());

        try {
            /** @var Result $result */
            $result = $builder->execute();

            foreach ($result as $payload) {
                $numberOfMessages++;
                yield $this->serializer->unserializePayload(json_decode($payload['payload'], true));
            }
        } catch (Throwable $exception) {
            throw UnableToRetrieveMessages::dueTo($exception->getMessage(), $exception);
        }

        return $cursor->plusOffset($numberOfMessages);
    }
}
