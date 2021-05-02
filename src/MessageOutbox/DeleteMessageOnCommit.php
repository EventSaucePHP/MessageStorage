<?php

namespace EventSauce\MessageOutbox;

use EventSauce\EventSourcing\Message;

class DeleteMessageOnCommit implements RelayCommitStrategy
{
    public function commitMessages(MessageOutboxRepository $repository, Message ...$messages): void
    {
        $repository->deleteMessages(...$messages);
    }
}
