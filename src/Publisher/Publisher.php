<?php

namespace IPresence\DomainEvents\Publisher;

use Exception;
use IPresence\DomainEvents\DomainEvent;
use IPresence\DomainEvents\Queue\Exception\WriterException;
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
     *
     * @throws WriterException
     */
    public function publish()
    {
        if (empty($this->events)) {
            return;
        }

        $this->write();
    }

    /**
     * Tries to write into the queue and retries if failed.
     *
     * @param int $retries
     *
     * @throws WriterException
     */
    private function write($retries = 0)
    {
        if ($retries > $this->retries) {
            $this->logger->error("Impossible to write to the queue after $retries retries");
            // TODO: decide alternative in case of failure
            return;
        }

        try {
            $this->writer->write($this->events);
        } catch (Exception $e) {
            $this->write($retries+1);
        }
    }
}