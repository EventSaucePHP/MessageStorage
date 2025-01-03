<?php

namespace EventSauce\MessageOutbox;

use EventSauce\BackOff\BackOffStrategy;
use EventSauce\BackOff\ExponentialBackOffStrategy;
use EventSauce\EventSourcing\Message;
use EventSauce\EventSourcing\MessageConsumer;
use Throwable;

use Traversable;
use function count;

class RelayMessagesThroughConsumer implements RelayMessages
{
    private BackOffStrategy $backOff;
    private RelayCommitStrategy $commitStrategy;

    public function __construct(
        private OutboxRepository $repository,
        private MessageConsumer $consumer,
        ?BackOffStrategy $backOff = null,
        ?RelayCommitStrategy $commitStrategy = null
    ) {
        $this->backOff = $backOff ?: new ExponentialBackOffStrategy(100000, 25);
        $this->commitStrategy = $commitStrategy ?: new MarkMessagesConsumedOnCommit();
    }

    public function publishBatch(int $batchSize, ?int $commitSize = 1): int
    {
        /** @var Traversable<Message> $messages */
        $messages = $this->repository->retrieveBatch($batchSize);
        $numberPublished = 0;
        /** @var Message[] $publishedMessages */
        $publishedMessages = [];

        foreach ($messages as $message) {
            $tries = 0;
            start_relay:
            try {
                $tries++;
                $this->consumer->handle($message);
                $publishedMessages[] = $message;

                if (($numberPublished + 1) % $commitSize === 0) {
                    $this->commitMessages($publishedMessages);
                    $publishedMessages = [];
                }
            } catch (Throwable $throwable) {
                $this->backOff->backOff($tries, $throwable);
                goto start_relay;
            }
            $numberPublished++;
        }

        if (count($publishedMessages) > 0) {
            $this->commitMessages($publishedMessages);
        }

        return $numberPublished;
    }

    /**
     * @param Message[] $messages
     */
    private function commitMessages(array $messages): void
    {
        $this->commitStrategy->commitMessages($this->repository, ...$messages);
    }
}
