<?php

namespace IPresence\DomainEvents\Queue\RabbitMQ\Consumer;

class RabbitMQConsumerConfig
{
    /**
     * @var bool
     */
    protected $noLocal;

    /**
     * @var bool
     */
    protected $noAck;

    /**
     * @var bool
     */
    protected $exclusive;

    /**
     * @var bool
     */
    protected $noWait;

    /**
     * @param bool $noLocal
     * @param bool $noAck
     * @param bool $exclusive
     * @param bool $noWait
     */
    public function __construct(bool $noLocal = false, bool $noAck = false, bool $exclusive = false, bool $noWait = false)
    {
        $this->noLocal = $noLocal;
        $this->noAck = $noAck;
        $this->exclusive = $exclusive;
        $this->noWait = $noWait;
    }

    /**
     * @return bool
     */
    public function noLocal(): bool
    {
        return $this->noLocal;
    }

    /**
     * @return bool
     */
    public function noAck(): bool
    {
        return $this->noAck;
    }

    /**
     * @return bool
     */
    public function exclusive(): bool
    {
        return $this->exclusive;
    }

    /**
     * @return bool
     */
    public function noWait(): bool
    {
        return $this->noWait;
    }
}
