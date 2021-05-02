<?php

namespace EventSauce\MessageOutbox;

use EventSauce\EventSourcing\Message;
use EventSauce\EventSourcing\MessageDispatcher;
use EventSauce\EventSourcing\UnableToDispatchMessages;
use Throwable;

class OutboxMessageDispatcher implements MessageDispatcher
{
    public function __construct(private MessageOutboxRepository $repository)
    {
    }

    public function dispatch(Message ...$messages): void
    {
        try {
            $this->repository->persist(...$messages);
        } catch (Throwable $exception) {
            throw UnableToDispatchMessages::dueTo('Exception occurred.', $exception);
        }
    }
}
