<?php

namespace EventSauce\MessageOutbox;

use EventSauce\EventSourcing\Message;
use Traversable;

interface OutboxRepository
{
    public function persist(Message ...$messages): void;

    /** @return Traversable<Message> */
    public function retrieveBatch(int $batchSize): Traversable;

    public function markConsumed(Message ...$messages): void;

    public function deleteMessages(Message ...$messages): void;

    public function cleanupConsumedMessages(int $amount): int;

    public function numberOfMessages(): int;

    public function numberOfConsumedMessages(): int;

    public function numberOfPendingMessages(): int;
}
