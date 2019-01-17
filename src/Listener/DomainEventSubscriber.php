<?php declare(strict_types=1);

namespace IPresence\DomainEvents\Listener;

use IPresence\DomainEvents\DomainEvent;

interface DomainEventSubscriber
{
    /**
     * @param DomainEvent $event
     *
     * @return bool
     */
    public function isSubscribed(DomainEvent $event): bool;

    /**
     * @param DomainEvent $event
     */
    public function execute(DomainEvent $event);
}
