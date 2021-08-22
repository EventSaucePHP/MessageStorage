<?php

namespace EventSauce\MessageRepository\DoctrineV2MessageRepository;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\ResultStatement;
use Doctrine\DBAL\Query\QueryBuilder;
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
use Ramsey\Uuid\Uuid;
use Throwable;

use Traversable;

use function array_keys;
use function array_map;
use function array_merge;
use function count;
use function implode;
use function json_decode;
use function json_encode;
use function sprintf;

class DoctrineUuidV4MessageRepository implements MessageRepository
{
    private TableSchema $tableSchema;
    private UuidEncoder $uuidEncoder;

    public function __construct(
        private Connection $connection,
        private string $tableName,
        private MessageSerializer $serializer,
        private int $jsonEncodeOptions = 0,
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
                $this->indexParameter('event_id', $index) => $this->uuidEncoder->encodeString($payload['headers'][Header::EVENT_ID]),
                $this->indexParameter('aggregate_root_id', $index) => $this->uuidEncoder->encodeString($payload['headers'][Header::AGGREGATE_ROOT_ID]),
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
        $builder->setParameter('aggregate_root_id', $this->uuidEncoder->encodeString($id->toString()));

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
        $builder->setParameter('aggregate_root_id', $this->uuidEncoder->encodeString($id->toString()));
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
}
