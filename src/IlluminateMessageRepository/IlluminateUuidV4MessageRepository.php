<?php

namespace EventSauce\IlluminateMessageRepository;

use EventSauce\EventSourcing\AggregateRootId;
use EventSauce\EventSourcing\Header;
use EventSauce\EventSourcing\Message;
use EventSauce\EventSourcing\MessageRepository;
use EventSauce\EventSourcing\Serialization\MessageSerializer;
use EventSauce\EventSourcing\UnableToPersistMessages;
use EventSauce\EventSourcing\UnableToRetrieveMessages;
use Generator;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Collection;
use Ramsey\Uuid\Uuid;
use Throwable;

use function count;

class IlluminateUuidV4MessageRepository implements MessageRepository
{
    private int $jsonEncodeOptions = 0;

    public function __construct(
        private ConnectionInterface $connection,
        private string $tableName,
        private MessageSerializer $serializer,
    ) {
    }

    public function persist(Message ...$messages): void
    {
        if (count($messages) === 0) {
            return;
        }

        $values = [];

        foreach ($messages as $message) {
            $parameters = [];
            $payload = $this->serializer->serializeMessage($message);
            $parameters['version'] = $payload['headers'][Header::AGGREGATE_ROOT_VERSION] ?? 0;
            $payload['headers'][Header::EVENT_ID] = $payload['headers'][Header::EVENT_ID] ?? Uuid::uuid4()->toString();
            $parameters['event_id'] = $this->uuidToBinary($payload['headers'][Header::EVENT_ID]);
            $parameters['payload'] = json_encode($payload, $this->jsonEncodeOptions);
            $parameters['aggregate_root_id'] = $this->uuidToBinary($payload['headers'][Header::AGGREGATE_ROOT_ID] ?? '');
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
            ->where('aggregate_root_id', $this->uuidToBinary($id->toString()))
            ->orderBy('version', 'ASC');

        try {
            return $this->yieldMessagesForResult($builder->get(['payload']));
        } catch (Throwable $exception) {
            throw UnableToRetrieveMessages::dueTo('', $exception);
        }
    }

    /** @psalm-return Generator<Message> */
    public function retrieveAllAfterVersion(AggregateRootId $id, int $aggregateRootVersion): Generator
    {
        $builder = $this->connection->table($this->tableName)
            ->where('aggregate_root_id', $this->uuidToBinary($id->toString()))
            ->where('version', '>', $aggregateRootVersion)
            ->orderBy('version', 'ASC');

        try {
            return $this->yieldMessagesForResult($builder->get(['payload']));
        } catch (Throwable $exception) {
            throw UnableToRetrieveMessages::dueTo('', $exception);
        }
    }

    /**
     * @psalm-return Generator<Message>
     */
    private function yieldMessagesForResult(Collection $result): Generator
    {
        foreach ($result as $row) {
            $message = $this->serializer->unserializePayload(json_decode($row->payload, true));
            yield $message;
        }

        return isset($message) ? $message->header(Header::AGGREGATE_ROOT_VERSION) ?: 0 : 0;
    }

    private function uuidToBinary(string $uuid): string
    {
        return Uuid::fromString($uuid)->getBytes();
    }
}
