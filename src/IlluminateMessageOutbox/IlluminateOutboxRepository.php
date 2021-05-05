<?php

namespace EventSauce\MessageOutbox\IlluminateMessageOutbox;

use EventSauce\EventSourcing\Message;
use EventSauce\EventSourcing\Serialization\MessageSerializer;
use EventSauce\MessageOutbox\OutboxRepository;
use Illuminate\Database\ConnectionInterface;
use Traversable;

use function array_map;
use function count;
use function json_decode;
use function json_encode;

class IlluminateOutboxRepository implements OutboxRepository
{
    public const ILLUMINATE_OUTBOX_MESSAGE_ID = '__illuminate_outbox.message_id';

    public function __construct(
        private ConnectionInterface $connection,
        private string $tableName,
        private MessageSerializer $serializer
    ) {
    }

    public function persist(Message ...$messages): void
    {
        if (count($messages) === 0) {
            return;
        }

        $inserts = array_map(
            function (Message $message) {
                return ['payload' => json_encode($this->serializer->serializeMessage($message))];
            },
            $messages
        );

        $this->connection->table($this->tableName)->insert($inserts);
    }

    public function retrieveBatch(int $batchSize): Traversable
    {
        $results = $this->connection->table($this->tableName)
            ->where('consumed', false)
            ->select()
            ->limit($batchSize)
            ->offset(0)
            ->get();

        foreach ($results as $row) {
            $payload = json_decode($row->payload, true);
            $message = $this->serializer->unserializePayload($payload);

            yield $message->withHeader(self::ILLUMINATE_OUTBOX_MESSAGE_ID, (int) $row->id);
        }
    }

    public function markConsumed(Message ...$messages): void
    {
        if (count($messages) === 0) {
            return;
        }

        $ids = array_map(
            fn(Message $message) => $this->idFromMessage($message),
            $messages,
        );

        $this->connection->table($this->tableName)->whereIn('id', $ids)->update(['consumed' => true]);
    }

    public function numberOfConsumedMessages(): int
    {
        return $this->connection->table($this->tableName)->where('consumed', true)->count('id');
    }

    public function numberOfPendingMessages(): int
    {
        return $this->connection->table($this->tableName)->where('consumed', false)->count('id');
    }

    public function numberOfMessages(): int
    {
        return $this->connection->table($this->tableName)->count('id');
    }

    public function cleanupConsumedMessages(int $amount): int
    {
        return $this->connection->table($this->tableName)->where('consumed', true)->orderBy('id', 'asc')->limit(
                $amount
            )->delete();
    }

    private function idFromMessage(Message $message): int
    {
        /** @var int|string $id */
        $id = $message->header(self::ILLUMINATE_OUTBOX_MESSAGE_ID);

        return (int) $id;
    }

    public function deleteMessages(Message ...$messages): void
    {
        if (count($messages) === 0) {
            return;
        }

        $ids = array_map(
            fn(Message $message) => $this->idFromMessage($message),
            $messages,
        );

        $this->connection->table($this->tableName)->whereIn('id', $ids)->delete();
    }
}
