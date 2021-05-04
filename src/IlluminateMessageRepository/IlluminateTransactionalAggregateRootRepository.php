<?php

namespace EventSauce\IlluminateMessageRepository;

use Doctrine\DBAL\Connection;
use EventSauce\EventSourcing\AggregateRootId;
use EventSauce\EventSourcing\AggregateRootRepository;
use EventSauce\EventSourcing\UnableToPersistMessages;
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
        private Connection $connection
    ) {
    }

    public function retrieve(AggregateRootId $aggregateRootId): object
    {
        return $this->aggregateRootRepository->retrieve($aggregateRootId);
    }

    public function persist(object $aggregateRoot): void
    {
        try {
            $this->connection->beginTransaction();

            try {
                $this->aggregateRootRepository->persist($aggregateRoot);
            } catch (Throwable $exception) {
                $this->connection->rollBack();
                throw $exception;
            }
            $this->connection->commit();
        } catch (Throwable $exception) {
            throw UnableToPersistMessages::dueTo('Failed DB transaction.', $exception);
        }
    }

    public function persistEvents(AggregateRootId $aggregateRootId, int $aggregateRootVersion, object ...$events): void
    {
        try {
            $this->connection->beginTransaction();

            try {
                $this->aggregateRootRepository->persistEvents($aggregateRootId, $aggregateRootVersion, ...$events);
            } catch (Throwable $exception) {
                $this->connection->rollBack();
                throw $exception;
            }
            $this->connection->commit();
        } catch (Throwable $exception) {
            throw UnableToPersistMessages::dueTo('Failed DB transaction.', $exception);
        }
    }
}
