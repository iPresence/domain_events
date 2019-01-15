<?php

namespace IPresence\DomainEvents\Queue\RabbitMQ;

use Exception;
use IPresence\DomainEvents\DomainEvent;
use IPresence\DomainEvents\Queue\Exception\WriterException;
use IPresence\DomainEvents\Queue\QueueWriter;
use IPresence\DomainEvents\Queue\RabbitMQ\Exchange\RabbitMQExchange;
use Psr\Log\LoggerInterface;

class RabbitMQWriter implements QueueWriter
{
    /**
     * @var RabbitMQExchange
     */
    private $exchange;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param RabbitMQExchange $exchange
     * @param LoggerInterface  $logger
     */
    public function __construct(RabbitMQExchange $exchange, LoggerInterface $logger)
    {
        $this->exchange = $exchange;
        $this->logger = $logger;
    }

    /**
     * @param DomainEvent[] $events
     *
     * @throws WriterException
     */
    public function write(array $events)
    {
        try {
            $this->exchange->create();
            $this->exchange->publish($events);
        } catch (Exception $e) {
            $this->logger->error('Error writing to the queue', ['exception' => $e->getMessage()]);
            throw new WriterException($e->getMessage(), $e->getCode());
        }
    }
}
