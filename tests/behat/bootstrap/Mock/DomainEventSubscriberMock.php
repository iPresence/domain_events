<?php

namespace behat\IPresence\DomainEvents\Mock;

use IPresence\DomainEvents\DomainEvent;
use IPresence\DomainEvents\Listener\DomainEventSubscriber;

class DomainEventSubscriberMock implements DomainEventSubscriber
{
    /**
     * @var string
     */
    private $eventName;

    /**
     * @var bool
     */
    private $executed = false;

    /**
     * @param string $eventName
     */
    public function __construct(string $eventName)
    {
        $this->eventName = $eventName;
    }

    /**
     * @param DomainEvent $event
     *
     * @return bool
     */
    public function isSubscribed(DomainEvent $event): bool
    {
        return $event->name() == $this->eventName;
    }

    /**
     * @param DomainEvent $event
     */
    public function execute(DomainEvent $event)
    {
        $this->executed = true;
    }

    /**
     * @return bool
     */
    public function wasExecuted(): bool
    {
        return $this->executed;
    }
}
