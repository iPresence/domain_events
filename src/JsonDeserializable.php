<?php

namespace IPresence\DomainEvents;

interface JsonDeserializable
{
    /**
     * @param array $data
     *
     * @return object
     */
    public static function jsonDeserialize(array $data);
}
