<?php

namespace EventSauce\MessageOutbox;

use EventSauce\EventSourcing\Message;

class MarkMessagesConsumedOnCommit implements RelayCommitStrategy
{
    public function commitMessages(OutboxRepository $repository, Message ...$messages): void
    {
        $repository->markConsumed(...$messages);
    }
}
