<?php

namespace EventSauce\MessageOutbox;

class InMemoryOutboxMessageRepositoryTestCase extends OutboxMessageRepositoryTestCase
{
    protected function outboxMessageRepository(): OutboxMessageRepository
    {
        return new InMemoryOutboxMessageRepository();
    }
}
