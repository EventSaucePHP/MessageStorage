<?php

namespace EventSauce\MessageOutbox;

use EventSauce\BackOff\BackOffStrategy;
use EventSauce\BackOff\ExponentialBackOffStrategy;
use EventSauce\EventSourcing\Message;
use EventSauce\EventSourcing\MessageConsumer;
use Throwable;

use function count;

/**
 * @deprecated
 *
 * @see RelayMessagesThroughConsumer
 */
class OutboxRelay extends RelayMessagesThroughConsumer
{
}
