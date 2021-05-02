<?php

namespace EventSauce\MessageOutbox;

use EventSauce\EventSourcing\Message;

interface RelayCommitStrategy
{
    public function commitMessages(MessageOutboxRepository $repository, Message ... $messages): void;
}
