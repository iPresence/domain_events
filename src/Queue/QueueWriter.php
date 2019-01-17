<?php declare(strict_types=1);

namespace IPresence\DomainEvents\Queue;

use IPresence\DomainEvents\DomainEvent;
use IPresence\DomainEvents\Queue\Exception\QueueException;

interface QueueWriter
{
    /**
     * @param DomainEvent[] $events
     *
     * @throws QueueException
     */
    public function write(array $events);
}
