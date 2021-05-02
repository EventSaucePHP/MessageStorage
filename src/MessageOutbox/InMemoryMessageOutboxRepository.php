<?php

namespace EventSauce\MessageOutbox;

use EventSauce\EventSourcing\Message;
use Traversable;

use function array_reduce;
use function array_slice;
use function count;

class InMemoryMessageOutboxRepository implements MessageOutboxRepository
{
    const MESSAGE_ID_HEADER = '__in-memory.message-id';
    const IS_CONSUMED_HEADER = '__in-memory.message-is-consumed';

    private int $idCounter = 0;

    /** @var array<int, Message> */
    private array $messages = [];

    public function persist(Message ...$messages): void
    {
        foreach ($messages as $message) {
            $id = ++$this->idCounter;
            $this->messages[$id] = $message->withHeader(self::MESSAGE_ID_HEADER, $id);
        }
    }

    public function retrieveBatch(int $batchSize): Traversable
    {
        /** @var list<Message> $messages */
        $messages = array_slice(array_values($this->messages), 0, $batchSize);

        foreach ($messages as $message) {
            if ($message->header(self::IS_CONSUMED_HEADER) !== 1) {
                yield $message;
            }
        }
    }

    public function markConsumed(Message ...$messages): void
    {
        foreach ($messages as $message) {
            $this->messages[$this->idFromMessage($message)] = $message->withHeader(self::IS_CONSUMED_HEADER, 1);
        }
    }

    private function idFromMessage(Message $message): int
    {
        /** @var int|string $id */
        $id = $message->header(self::MESSAGE_ID_HEADER);

        return (int) $id;
    }

    public function deleteMessages(Message ...$messages): void
    {
        foreach ($messages as $message) {
            unset($this->messages[$this->idFromMessage($message)]);
        }
    }

    public function cleanupConsumedMessages(int $amount): int
    {
        $deleted = 0;

        foreach ($this->messages as $message) {
            if ($message->header(self::IS_CONSUMED_HEADER) === 1) {
                unset($this->messages[$this->idFromMessage($message)]);
                $deleted++;
            }

            if ($deleted >= $amount) {
                break;
            }
        }

        return $deleted;
    }

    public function numberOfMessages(): int
    {
        return count($this->messages);
    }

    public function numberOfConsumedMessages(): int
    {
        return array_reduce(
            $this->messages,
            fn(int $count, Message $message) => $message->header(self::IS_CONSUMED_HEADER) === 1 ? $count + 1 : $count,
            0
        );
    }

    public function numberOfPendingMessages(): int
    {
        return array_reduce(
            $this->messages,
            fn(int $count, Message $message) => $message->header(self::IS_CONSUMED_HEADER) === 1 ? $count : $count + 1,
            0
        );
    }
}
