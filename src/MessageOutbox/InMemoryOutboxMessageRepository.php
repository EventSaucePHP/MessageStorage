<?php

namespace EventSauce\MessageOutbox;

use ArrayIterator;
use EventSauce\EventSourcing\Message;

use Traversable;

use function array_slice;

class InMemoryOutboxMessageRepository implements OutboxMessageRepository
{
    const MESSAGE_ID_HEADER = '__in-memory.message-id';

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
            yield $message;
        }
    }

    public function markConsumed(Message ...$messages): void
    {
        foreach ($messages as $message) {
            unset($this->messages[((int) $message->header(self::MESSAGE_ID_HEADER))]);
        }
    }
}
