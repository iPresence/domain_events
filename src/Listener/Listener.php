<?php declare(strict_types=1);

namespace IPresence\DomainEvents\Listener;

use IPresence\DomainEvents\DomainEvent;
use IPresence\DomainEvents\DomainEventFactory;
use IPresence\DomainEvents\Queue\QueueReader;
use IPresence\Monitoring\Monitor;
use Psr\Log\LoggerInterface;

class Listener
{
    private const DEFAULT_IDLE_SLEEP = 200000;

    /**
     * @var QueueReader[]
     */
    private $readers;

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
     * @var int
     */
    private $idleSleep;

    /**
     * @param QueueReader[]           $readers
     * @param DomainEventFactory      $factory
     * @param Monitor                 $monitor
     * @param LoggerInterface         $logger
     * @param DomainEventSubscriber[] $subscribers
     * @param int                     $idleSleep
     */
    public function __construct(
        array $readers,
        DomainEventFactory $factory,
        Monitor $monitor,
        LoggerInterface $logger,
        array $subscribers = [],
        int $idleSleep = self::DEFAULT_IDLE_SLEEP
    ) {
        $this->readers = $readers;
        $this->factory = $factory;
        $this->monitor = $monitor;
        $this->logger = $logger;
        $this->subscribers = $subscribers;
        $this->idleSleep = $idleSleep;
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
     * Listen to domain events without interruption while cycling readers
     */
    public function listen()
    {
        $messagesRead = 0;
        foreach ($this->readers as $reader) {
            try {
                $messagesRead += $reader->read([$this, 'notify']);
            } catch (\Throwable $e) {
                $this->logger->error('Unexpected exception while reading events', ['exception' => $e]);
            }
        }

        if ($messagesRead <= 0) {
            usleep($this->idleSleep);
        }
    }

    /**
     * @param string $json
     *
     * @throws \Exception
     */
    public function notify(string $json)
    {
        $event = $this->factory->fromJSON($json);

        $this->monitor->increment('domain_event.received', ['name' => $event->name()]);
        $this->logger->debug("Domain Event {$event->name()} received, notifying subscribers");

        foreach ($this->subscribers as $subscriber) {
            if ($subscriber->isSubscribed($event)) {
                $this->execute($subscriber, $event);
            }
        }
    }

    /**
     * @param DomainEventSubscriber $subscriber
     * @param DomainEvent           $event
     */
    private function execute(DomainEventSubscriber $subscriber, DomainEvent $event)
    {
        $this->monitor->start('domain_event.consumed', [
            'name' => $event->name(),
            'subscriber' => get_class($subscriber),
        ]);

        try {
            $subscriber->execute($event);
            $this->monitor->end('domain_event.consumed', ['success' => true]);
        } catch (\Throwable $e) {
            $this->monitor->end('domain_event.consumed', ['success' => false]);
            $this->logger->error("Error handling domain event {$event->name()}", [
                'exception' => $e,
                ''
            ]);
        }
    }
}
