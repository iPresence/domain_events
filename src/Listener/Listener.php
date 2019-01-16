<?php

namespace IPresence\DomainEvents\Listener;

use Exception;
use IPresence\DomainEvents\DomainEventFactory;
use IPresence\DomainEvents\Queue\Exception\TimeoutException;
use IPresence\DomainEvents\Queue\QueueReader;
use IPresence\Monitoring\Monitor;
use Psr\Log\LoggerInterface;

class Listener
{
    /**
     * @var QueueReader
     */
    private $reader;

    /**
     * @var DomainEventFactory
     */
    private $factory;

    /**
     * @var Monitor
     */
    private $monitor;

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
     * @param DomainEventFactory      $factory
     * @param Monitor                 $monitor
     * @param LoggerInterface         $logger
     * @param DomainEventSubscriber[] $subscribers
     */
    public function __construct(
        QueueReader $reader,
        DomainEventFactory $factory,
        Monitor $monitor,
        LoggerInterface $logger,
        array $subscribers = []
    ) {
        $this->reader = $reader;
        $this->factory = $factory;
        $this->monitor = $monitor;
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
     */
    public function listen($timeout = 0)
    {
        while (true) {
            try {
                $this->reader->read([$this, 'notify'], $timeout);
            } catch (TimeoutException $e) {
                $this->logger->error('Timeout while listening for events', ['exception' => $e->getMessage()]);
                break;
            } catch (Exception $e) {
                $this->logger->error('Not controllable exception, discarding the event', ['exception' => $e->getMessage()]);
            }
        }
    }

    /**
     * @param string $json
     *
     * @throws Exception
     */
    public function notify(string $json)
    {
        $event = $this->factory->fromJSON($json);

        $this->monitor->increment('domain_event.received', ['name' => $event->name()]);
        $this->logger->debug("Domain Event {$event->name()} received, notifying subscribers");

        foreach ($this->subscribers as $subscriber) {
            if ($subscriber->isSubscribed($event)) {
                $subscriber->execute($event);
            }
        }
    }
}