<?php

namespace EventSauce\MessageOutbox;

use EventSauce\EventSourcing\Message;

class MarkMessagesConsumedOnCommit implements RelayCommitStrategy
{
    public function commitMessages(MessageOutboxRepository $repository, Message ...$messages): void
    {
        $repository->markConsumed(...$messages);
    }
}
