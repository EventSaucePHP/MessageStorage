<?php

namespace EventSauce\MessageOutbox\Illuminate;

use EventSauce\EventSourcing\AggregateRootId;
use EventSauce\EventSourcing\AggregateRootRepository;
use Illuminate\Database\ConnectionInterface;

class IlluminateTransactionalAggregateRootRepository implements AggregateRootRepository
{
    public function __construct(
        private ConnectionInterface $connection,
        private AggregateRootRepository $repository
    ) {
    }

    public function retrieve(AggregateRootId $aggregateRootId): object
    {
        return $this->repository->retrieve($aggregateRootId);
    }

    public function persist(object $aggregateRoot): void
    {
        $this->connection->transaction(fn() => $this->repository->persist($aggregateRoot));
    }

    public function persistEvents(AggregateRootId $aggregateRootId, int $aggregateRootVersion, object ...$events): void
    {
        $this->connection->transaction(fn() =>
            $this->repository->persistEvents($aggregateRootId, $aggregateRootVersion, ...$events));
    }
}
