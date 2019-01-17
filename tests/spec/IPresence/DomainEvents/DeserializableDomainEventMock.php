<?php

namespace spec\IPresence\DomainEvents;

use IPresence\DomainEvents\DomainEvent;

class DeserializableDomainEventMock extends DomainEvent
{
    public function __construct()
    {
    }

    public static function jsonDeserialize(array $data)
    {
        return new DeserializableDomainEventMock();
    }

}
