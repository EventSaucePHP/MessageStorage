<?php

namespace EventSauce\MessageOutbox\DoctrineOutbox;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use EventSauce\EventSourcing\Message;
use EventSauce\EventSourcing\Serialization\MessageSerializer;
use EventSauce\MessageOutbox\OutboxRepository;
use Traversable;

use function count;

class DoctrineOutboxRepository implements OutboxRepository
{
    public const DOCTRINE_OUTBOX_MESSAGE_ID = '__doctrine_outbox.message_id';

    public function __construct(
        private Connection $connection,
        private string $tableName,
        private MessageSerializer $serializer
    ) {}

    public function persist(Message ...$messages): void
    {
        $numberOfMessages = count($messages);

        if ($numberOfMessages === 0) {
            return;
        }

        $sqlQuery = "INSERT INTO {$this->tableName} (payload) VALUES " .  implode(', ', array_fill(0, $numberOfMessages, '(?)')) ;
        $statement = $this->connection->prepare($sqlQuery);
        $index = 0;

        foreach ($messages as $message) {
            $statement->bindValue(++$index, json_encode($this->serializer->serializeMessage($message)));
        }

        $statement->executeQuery();
    }

    public function retrieveBatch(int $batchSize): Traversable
    {
        $sqlQuery = "SELECT id, payload FROM {$this->tableName} WHERE consumed = FALSE ORDER BY id ASC LIMIT ? ";
        $statement = $this->connection->prepare($sqlQuery);
        $statement->bindValue(1, $batchSize, ParameterType::INTEGER);
        $result = $statement->executeQuery();

        while($row = $result->fetchAssociative()) {
            $message = $this->serializer->unserializePayload(json_decode($row['payload'], true));

            yield $message->withHeader(self::DOCTRINE_OUTBOX_MESSAGE_ID, (int) $row['id']);
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

        $sqlStatement = "UPDATE {$this->tableName} SET consumed = TRUE WHERE id IN (:ids)";
        $this->connection->executeQuery($sqlStatement, [
            'ids' => $ids,
        ], [
            'ids' => $this->intArrayType(),
        ]);
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

        $sqlStatement = "DELETE FROM {$this->tableName} WHERE id IN (:ids)";
        $this->connection->executeQuery($sqlStatement, [
            'ids' => $ids,
        ], [
            'ids' => $this->intArrayType(),
        ]);
    }

    public function cleanupConsumedMessages(int $amount): int
    {
        $sqlStatement = "DELETE FROM {$this->tableName} WHERE consumed = TRUE LIMIT ?";
        $statement = $this->connection->prepare($sqlStatement);
        $statement->bindValue(1, $amount, ParameterType::INTEGER);

        return $statement->executeStatement();
    }

    public function numberOfMessages(): int
    {
        $statement = $this->connection->prepare("SELECT COUNT(id) FROM {$this->tableName}");

        return $statement->executeQuery()->fetchFirstColumn()[0];
    }

    public function numberOfConsumedMessages(): int
    {
        $statement = $this->connection->prepare("SELECT COUNT(`id`) FROM `{$this->tableName}` WHERE `consumed` = TRUE");

        return $statement->executeQuery()->fetchFirstColumn()[0];
    }

    public function numberOfPendingMessages(): int
    {
        $statement = $this->connection->prepare("SELECT COUNT(`id`) FROM `{$this->tableName}` WHERE `consumed` = FALSE");

        return $statement->executeQuery()->fetchFirstColumn()[0];
    }

    private function idFromMessage(Message $message): int
    {
        /** @var int|string $id */
        $id = $message->header(self::DOCTRINE_OUTBOX_MESSAGE_ID);

        return (int) $id;
    }

    private function intArrayType(): mixed
    {
        if (class_exists(ArrayParameterType::class)) {
            return ArrayParameterType::INTEGER;
        }

        return Connection::PARAM_INT_ARRAY;
    }
}
