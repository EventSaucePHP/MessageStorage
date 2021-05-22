<?php

namespace EventSauce\MessageRepository\DoctrineV2MessageRepository;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\ResultStatement;
use EventSauce\EventSourcing\AggregateRootId;
use EventSauce\EventSourcing\Header;
use EventSauce\EventSourcing\Message;
use EventSauce\EventSourcing\MessageRepository;
use EventSauce\EventSourcing\Serialization\MessageSerializer;
use EventSauce\EventSourcing\UnableToPersistMessages;
use EventSauce\EventSourcing\UnableToRetrieveMessages;
use Generator;
use Ramsey\Uuid\Uuid;
use Throwable;

use function count;

class DoctrineUuidV4MessageRepository implements MessageRepository
{
    private int $jsonEncodeOptions = 0;

    public function __construct(
        private Connection $connection,
        private string $tableName,
        private MessageSerializer $serializer,
    ) {}

    public function persist(Message ...$messages): void
    {
        if (count($messages) === 0) {
            return;
        }

        $sql = "INSERT INTO {$this->tableName} (event_id, aggregate_root_id, version, payload) VALUES ";
        $values = [];
        $parameters = [];

        foreach ($messages as $index => $message) {
            $payload = $this->serializer->serializeMessage($message);
            $eventIdColumn = 'event_id_' . $index;
            $aggregateRootIdColumn = 'aggregate_root_id_' . $index;
            $versionColumn = 'version_' . $index;
            $payloadColumn = 'payload_' . $index;
            $values[] = "(:{$eventIdColumn}, :{$aggregateRootIdColumn}, :{$versionColumn}, :{$payloadColumn})";
            $parameters[$versionColumn] = $payload['headers'][Header::AGGREGATE_ROOT_VERSION] ?? 0;
            $payload['headers'][Header::EVENT_ID] = $payload['headers'][Header::EVENT_ID] ?? Uuid::uuid4()->toString();
            $parameters[$eventIdColumn] = $this->uuidToBinary($payload['headers'][Header::EVENT_ID]);
            $parameters[$payloadColumn] = json_encode($payload, $this->jsonEncodeOptions);
            $parameters[$aggregateRootIdColumn] = $this->uuidToBinary($payload['headers'][Header::AGGREGATE_ROOT_ID] ?? '');
        }

        try {
            $sql .= implode(', ', $values);
            $this->connection->prepare($sql)->execute($parameters);
        } catch (Throwable $exception) {
            throw UnableToPersistMessages::dueTo('', $exception);
        }
    }

    public function retrieveAll(AggregateRootId $id): Generator
    {
        $builder = $this->connection->createQueryBuilder()
            ->select('payload')
            ->from($this->tableName)
            ->where('aggregate_root_id = :aggregate_root_id')
            ->orderBy('version', 'ASC')
            ->setParameter('aggregate_root_id', $this->uuidToBinary($id->toString()));

        try {
            /** @var ResultStatement $resultStatement */
            $resultStatement = $builder->execute();

            return $this->yieldMessagesForResult($resultStatement);
        } catch (Throwable $exception) {
            throw UnableToRetrieveMessages::dueTo('', $exception);
        }
    }

    /** @psalm-return Generator<Message> */
    public function retrieveAllAfterVersion(AggregateRootId $id, int $aggregateRootVersion): Generator
    {
        $builder = $this->connection->createQueryBuilder()
            ->select('payload')
            ->from($this->tableName)
            ->where('aggregate_root_id = :aggregate_root_id')
            ->andWhere('version > :version')
            ->orderBy('version', 'ASC')
            ->setParameter('aggregate_root_id', $this->uuidToBinary($id->toString()))
            ->setParameter('version', $aggregateRootVersion);

        try {
            /** @var ResultStatement $resultStatement */
            $resultStatement = $builder->execute();

            return $this->yieldMessagesForResult($resultStatement);
        } catch (Throwable $exception) {
            throw UnableToRetrieveMessages::dueTo('', $exception);
        }
    }

    /**
     * @psalm-return Generator<Message>
     */
    private function yieldMessagesForResult(ResultStatement $stm): Generator
    {
        while ($payload = $stm->fetchColumn()) {
            $message = $this->serializer->unserializePayload(json_decode($payload, true));
            yield $message;
        }

        return isset($message)
            ? $message->header(Header::AGGREGATE_ROOT_VERSION) ?: 0
            : 0;
    }

    private function uuidToBinary(string $uuid): string
    {
        return Uuid::fromString($uuid)->getBytes();
    }
}
