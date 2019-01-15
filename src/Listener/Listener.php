<?php

namespace IPresence\DomainEvents\Listener;

use IPresence\DomainEvents\DomainEvent;
use IPresence\DomainEvents\Queue\Exception\GracefulStopException;
use IPresence\DomainEvents\Queue\Exception\ReaderException;
use IPresence\DomainEvents\Queue\Exception\TimeoutException;
use IPresence\DomainEvents\Queue\QueueReader;
use Psr\Log\LoggerInterface;

class Listener
{
    /**
     * @var QueueReader
     */
    private $reader;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var DomainEventSubscriber[]
     */
    private $subscribers = [];

    /**
     * @param QueueReader             $reader
     * @param LoggerInterface         $logger
     * @param DomainEventSubscriber[] $subscribers
     */
    public function __construct(QueueReader $reader, LoggerInterface $logger, array $subscribers = [])
    {
        $this->reader = $reader;
        $this->logger = $logger;
        $this->subscribers = $subscribers;
    }

    /**
     * @param DomainEventSubscriber $subscriber
     *
     * @return $this
     */
    public function subscribe(DomainEventSubscriber $subscriber)
    {
        $this->subscribers[] = $subscriber;
        return $this;
    }

    /**
     * @param int $timeout
     *
     * @throws ReaderException
     */
    public function listen($timeout = 0)
    {
        while (true) {
            try {
                $this->reader->read([$this, 'notify'], $timeout);
            } catch (TimeoutException $e) {
                break;
            } catch (GracefulStopException $e) {
                break;
            }
        }
    }

    /**
     * @param DomainEvent $event
     */
    public function notify(DomainEvent $event)
    {
        $this->logger->debug("Domain Event {$event->name()} received, notifying subscribers");
        foreach ($this->subscribers as $subscriber) {
            if ($subscriber->isSubscribed($event)) {
                $subscriber->execute($event);
            }
        }
    }
}