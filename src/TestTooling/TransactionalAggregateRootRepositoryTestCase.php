<?php

namespace EventSauce\MessageOutbox\TestTooling;

use EventSauce\EventSourcing\AggregateRootRepository;
use EventSauce\MessageOutbox\MessageOutboxRepository;
use PHPStan\Testing\TestCase;

abstract class TransactionalAggregateRootRepositoryTestCase extends TestCase
{
    abstract protected function messageOutboxRepository(): MessageOutboxRepository;
    abstract protected function aggregateRootRepository(): AggregateRootRepository;

    /**
     * @test
     */
    public function successfully_storing_an_aggregate(): void
    {

    }
}
