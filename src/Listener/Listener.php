<?php declare(strict_types=1);

namespace IPresence\DomainEvents\Listener;

use Exception;
use IPresence\DomainEvents\DomainEvent;
use IPresence\DomainEvents\DomainEventFactory;
use IPresence\DomainEvents\Queue\Exception\StopReadingException;
use IPresence\DomainEvents\Queue\QueueReader;
use IPresence\Monitoring\Monitor;
use Psr\Log\LoggerInterface;

class Listener
{
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
     * @param QueueReader[]           $readers
     * @param DomainEventFactory      $factory
     * @param Monitor                 $monitor
     * @param LoggerInterface         $logger
     * @param DomainEventSubscriber[] $subscribers
     */
    public function __construct(
        array $readers,
        DomainEventFactory $factory,
        Monitor $monitor,
        LoggerInterface $logger,
        array $subscribers = []
    ) {
        $this->readers = $readers;
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
            foreach ($this->readers as $reader) {
                $this->read($reader, $timeout);
            }
        }
    }

    /**
     * Reads the message from the queue.
     * Will return when there are no more messages to consume during the $timeout time.
     *
     * @param QueueReader $reader
     * @param int         $timeout
     */
    private function read(QueueReader $reader, int $timeout)
    {
        while (true) {
            try {
                $reader->read([$this, 'notify'], $timeout);
            } catch(StopReadingException $e) {
                break;
            } catch (Exception $e) {
                $this->logger->error('Not controllable exception while reading events', ['exception' => $e->getMessage()]);
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
        } catch (Exception $e) {
            $this->monitor->end('domain_event.consumed', ['success' => false]);
            $this->logger->error("Domain Event {$event->name()} received, notifying subscribers");
        }
    }
}
