<?php

namespace EventSauce\MessageOutbox;

use EventSauce\MessageOutbox\TestTooling\OutboxMessageRepositoryTestCase;

class InMemoryOutboxMessageRepositoryTest extends OutboxMessageRepositoryTestCase
{
    protected function outboxMessageRepository(): OutboxMessageRepository
    {
        return new InMemoryOutboxMessageRepository();
    }
}
