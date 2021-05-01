<?php

namespace EventSauce\MessageOutbox;

use EventSauce\BackOff\BackOffStrategy;
use EventSauce\BackOff\ExponentialBackOffStrategy;
use EventSauce\EventSourcing\Message;
use EventSauce\EventSourcing\MessageConsumer;
use Throwable;

use function count;

class OutboxMessageRelay
{
    private BackOffStrategy $backOff;

    public function __construct(
        private OutboxMessageRepository $repository,
        private MessageConsumer $consumer,
        BackOffStrategy $backOff = null
    ) {
        $this->backOff = $backOff ?: new ExponentialBackOffStrategy(100000, 25);
    }

    public function publishBatch(int $batchSize, int $commitSize = 1): int
    {
        /** @var list<Message> $messages */
        $messages = $this->repository->retrieveBatch($batchSize);
        $numberPublished = 0;
        /** @var list<Message> $publishedMessages */
        $publishedMessages = [];

        foreach ($messages as $message) {
            $tries = 0;
            start_relay:
            try {
                $tries++;
                $this->consumer->handle($message);
                $publishedMessages[] = $message;

                if (($numberPublished + 1) % $commitSize === 0) {
                    $this->repository->markConsumed(...$publishedMessages);
                    $publishedMessages = [];
                }
            } catch (Throwable $throwable) {
                $this->backOff->backOff($tries, $throwable);
                goto start_relay;
            }
            $numberPublished++;
        }

        if (count($publishedMessages) > 0) {
            $this->repository->markConsumed(...$publishedMessages);
        }

        return $numberPublished;
    }
}
