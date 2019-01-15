<?php

namespace IPresence\DomainEvents;

use InvalidArgumentException;

class DomainEventFactory
{
    /**
     * @var array
     */
    private $mapping;

    /**
     * @param array $mapping
     */
    public function __construct(array $mapping = [])
    {
        $this->mapping = $mapping;
    }

    /**
     * @param string $json
     *
     * @return DomainEvent
     * @throws \Exception
     */
    public function fromJSON(string $json): DomainEvent
    {
        $data = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException('Invalid JSON: '.json_last_error_msg());
        }

        if (!isset($data['id'], $data['origin'], $data['name'], $data['version'], $data['occurredOn'], $data['body'])) {
            throw new InvalidArgumentException("Cannot reconstruct domain event, JSON received: $json");
        }

        return $this->create($data['name'], $data);
    }

    /**
     * @param string $eventName
     * @param array  $data
     *
     * @return DomainEvent
     * @throws \Exception
     */
    private function create(string $eventName, array $data): DomainEvent
    {
        foreach ($this->mapping as $name => $class) {
            if ($name == $eventName) {
                if (in_array(JsonDeserializable::class, class_implements($class))) {
                    return $class::jsonDeserialize($data);
                }

                throw new InvalidArgumentException("Class $class does not implement JsonDeserialize interface");
            }
        }

        return DomainEvent::jsonDeserialize($data);
    }
}
