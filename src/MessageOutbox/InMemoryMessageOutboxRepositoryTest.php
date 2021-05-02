<?php

namespace EventSauce\MessageOutbox;

use EventSauce\MessageOutbox\TestTooling\OutboxMessageRepositoryTestCase;

class InMemoryMessageOutboxRepositoryTest extends OutboxMessageRepositoryTestCase
{
    protected function outboxMessageRepository(): MessageOutboxRepository
    {
        return new InMemoryMessageOutboxRepository();
    }
}
