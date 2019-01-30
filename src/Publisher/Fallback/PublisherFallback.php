<?php declare(strict_types=1);

namespace IPresence\DomainEvents\Publisher\Fallback;

use IPresence\DomainEvents\DomainEvent;

interface PublisherFallback
{
    /**
     * @param DomainEvent[] $events
     */
    public function store(array $events);

    /**
     * @return DomainEvent[]
     */
    public function restore(): array;
}
