<?php

namespace IPresence\DomainEvents\Publisher;

use Exception;
use IPresence\DomainEvents\DomainEvent;
use IPresence\DomainEvents\Queue\Exception\QueueException;
use IPresence\DomainEvents\Queue\QueueWriter;
use Psr\Log\LoggerInterface;

class Publisher
{
    /**
     * @var QueueWriter
     */
    private $writer;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var int
     */
    private $retries;

    /**
     * @var DomainEvent[]
     */
    private $events = [];

    /**
     * @param QueueWriter     $writer
     * @param LoggerInterface $logger
     * @param int             $retries
     */
    public function __construct(QueueWriter $writer, LoggerInterface $logger, int $retries = 3)
    {
        $this->writer = $writer;
        $this->logger = $logger;
        $this->retries = $retries;
    }

    /**
     * @param DomainEvent $event
     */
    public function add(DomainEvent $event)
    {
        $this->events[] = $event;
    }

    /**
     * Publishes all the added events to the queue.
     */
    public function publish()
    {
        if (empty($this->events)) {
            return;
        }

        try {
            $this->write($this->events);
            $this->events = [];
        } catch (QueueException $e) {
            $this->logger->error("Exception while writing to the queue", ['exception' => $e->getMessage()]);
        }

    }

    /**
     * Tries to write into the queue and retries if failed.
     *
     * @param array $events
     * @param int   $retries
     *
     * @throws QueueException
     */
    private function write(array $events, $retries = 0)
    {
        if ($retries > $this->retries) {
            throw new QueueException("Impossible to write to the queue after $retries retries, storing the events");
        }

        try {
            $this->writer->write($events);
        } catch (Exception $e) {
            $this->write($events, $retries+1);
        }
    }
}