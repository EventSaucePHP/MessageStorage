<?php

namespace EventSauce\MessageOutbox\TestTooling;

use EventSauce\EventSourcing\AggregateRoot;
use EventSauce\EventSourcing\AggregateRootBehaviour;

/**
 * @codeCoverageIgnore
 */
class DummyAggregate implements AggregateRoot
{
    use AggregateRootBehaviour;

    private string $lastValue = '';

    public function performAction(string $value): void
    {
        $this->recordThat(new DummyEvent($value));
    }

    public function lastValue(): string
    {
        return $this->lastValue;
    }

    protected function applyDummyEvent(DummyEvent $event)
    {
        $this->lastValue = $event->value;
    }
}
