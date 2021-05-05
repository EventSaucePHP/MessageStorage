<?php

namespace EventSauce\MessageOutbox;

use EventSauce\EventSourcing\Message;

interface RelayCommitStrategy
{
    public function commitMessages(OutboxRepository $repository, Message ... $messages): void;
}
