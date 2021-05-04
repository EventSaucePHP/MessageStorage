<?php

namespace EventSauce\MessageOutbox\IlluminateMessageOutbox;

use EventSauce\EventSourcing\AggregateRootId;
use EventSauce\EventSourcing\AggregateRootRepository;
use EventSauce\EventSourcing\UnableToPersistMessages;
use Illuminate\Database\ConnectionInterface;
use Throwable;

/**
 * @template            T of AggregateRoot
 *
 * @template-implements AggregateRootRepository<T>
 */
class IlluminateTransactionalAggregateRootRepository implements AggregateRootRepository
{
    /**
     * @param AggregateRootRepository<T> $aggregateRootRepository
     */
    public function __construct(
        private AggregateRootRepository $aggregateRootRepository,
        private ConnectionInterface $connection
    ) {
    }

    public function retrieve(AggregateRootId $aggregateRootId): object
    {
        return $this->aggregateRootRepository->retrieve($aggregateRootId);
    }

    public function persist(object $aggregateRoot): void
    {
        try {
            $this->connection->transaction(fn() => $this->aggregateRootRepository->persist($aggregateRoot));
        } catch (Throwable $exception) {
            throw UnableToPersistMessages::dueTo('Failed database transaction.', $exception);
        }
    }

    public function persistEvents(AggregateRootId $aggregateRootId, int $aggregateRootVersion, object ...$events): void
    {
        try {
            $this->connection->transaction(
                fn() => $this->aggregateRootRepository->persistEvents($aggregateRootId, $aggregateRootVersion, ...$events)
            );
        } catch (Throwable $exception) {
            throw UnableToPersistMessages::dueTo('Failed database transaction.', $exception);
        }
    }
}
