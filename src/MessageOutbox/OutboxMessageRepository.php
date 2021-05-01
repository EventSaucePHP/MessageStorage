<?php

namespace EventSauce\MessageOutbox;

use EventSauce\EventSourcing\Message;
use Traversable;

interface OutboxMessageRepository
{
    public function persist(Message ... $messages): void;

    public function retrieveBatch(int $batchSize): Traversable;

    public function markConsumed(Message ... $messages): void;
}
