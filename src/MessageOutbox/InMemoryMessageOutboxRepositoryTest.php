<?php

namespace EventSauce\MessageOutbox;

use EventSauce\MessageOutbox\TestTooling\OutboxRepositoryTestCase;

class InMemoryMessageOutboxRepositoryTest extends OutboxRepositoryTestCase
{
    protected function outboxMessageRepository(): OutboxRepository
    {
        return new InMemoryOutboxRepository();
    }
}
