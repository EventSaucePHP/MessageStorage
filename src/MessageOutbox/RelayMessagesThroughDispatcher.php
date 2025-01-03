<?php
declare(strict_types=1);

namespace EventSauce\MessageOutbox;

use EventSauce\BackOff\BackOffStrategy;
use EventSauce\BackOff\ExponentialBackOffStrategy;
use EventSauce\EventSourcing\Message;
use EventSauce\EventSourcing\MessageDispatcher;
use Throwable;
use Traversable;

use function count;

final class RelayMessagesThroughDispatcher implements RelayMessages
{
    private BackOffStrategy $backOff;
    private RelayCommitStrategy $commitStrategy;

    public function __construct(
        private OutboxRepository $repository,
        private MessageDispatcher $dispatcher,
        ?BackOffStrategy $backOff = null,
        ?RelayCommitStrategy $commitStrategy = null,
    ) {
        $this->backOff = $backOff ?? new ExponentialBackOffStrategy(100000, 25);
        $this->commitStrategy = $commitStrategy ?? new MarkMessagesConsumedOnCommit();
    }

    public function publishBatch(int $batchSize, ?int $commitSize = 1): int
    {
        $numberPublished = 0;
        $messages = $this->repository->retrieveBatch($batchSize);

        foreach ($this->batchByCommitSize($messages, $commitSize ?? 1) as $batch) {
            $numberPublished += $this->dispatchMessages($batch);
        }

        return $numberPublished;
    }

    /**
     * @param Traversable<Message> $messages
     *
     * @return iterable<list<Message>>
     */
    private function batchByCommitSize(Traversable $messages, int $size): iterable
    {
        $batch = [];

        foreach ($messages as $message) {
            $batch[] = $message;

            if (count($batch) === $size) {
                yield $batch;

                $batch = [];
            }
        }

        if (count($batch) > 0) {
            yield $batch;
        }
    }

    /** @param list<Message> $messages */
    private function dispatchMessages(array $messages): int
    {
        $tries = 0;
        start_relay:

        try {
            $tries++;
            $this->dispatcher->dispatch(...$messages);
        } catch (Throwable $throwable) {
            $this->backOff->backOff($tries, $throwable);
            goto start_relay;
        }

        $this->commitStrategy->commitMessages($this->repository, ...$messages);

        return count($messages);
    }
}
