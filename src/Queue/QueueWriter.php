<?php

namespace IPresence\DomainEvents\Queue;

use IPresence\DomainEvents\DomainEvent;
use IPresence\DomainEvents\Queue\Exception\WriterException;

interface QueueWriter
{
    /**
     * @param DomainEvent[] $events
     *
     * @throws WriterException
     */
    public function write(array $events);
}
