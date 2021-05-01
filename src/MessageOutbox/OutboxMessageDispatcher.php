<?php

namespace EventSauce\MessageOutbox;

use EventSauce\EventSourcing\Message;
use EventSauce\EventSourcing\MessageDispatcher;
use EventSauce\EventSourcing\UnableToDispatchMessages;

class OutboxMessageDispatcher implements MessageDispatcher
{
    public function __construct(private OutboxMessageRepository $repository)
    {
    }

    public function dispatch(Message ...$messages): void
    {
        $this->repository->persist(...$messages);
    }
}
