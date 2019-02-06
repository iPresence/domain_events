<?php declare(strict_types=1);

namespace IPresence\DomainEvents\Publisher;

use Exception;
use IPresence\DomainEvents\DomainEvent;
use IPresence\DomainEvents\Publisher\Fallback\PublisherFallback;
use IPresence\DomainEvents\Queue\Exception\QueueException;
use IPresence\DomainEvents\Queue\QueueWriter;
use IPresence\Monitoring\Monitor;
use Psr\Log\LoggerInterface;

class Publisher
{
    /**
     * @var QueueWriter[]
     */
    private $writers;

    /**
     * @var PublisherFallback
     */
    private $fallback;

    /**
     * @var Monitor
     */
    private $monitor;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var int
     */
    private $maxRetries;

    /**
     * @var DomainEvent[]
     */
    private $events = [];

    /**
     * @param QueueWriter[]     $writers
     * @param PublisherFallback $fallback
     * @param Monitor           $monitor
     * @param LoggerInterface   $logger
     * @param int               $maxRetries
     */
    public function __construct(
        array $writers,
        PublisherFallback $fallback,
        Monitor $monitor,
        LoggerInterface $logger,
        int $maxRetries = 3
    ) {
        $this->writers = $writers;
        $this->fallback = $fallback;
        $this->monitor = $monitor;
        $this->logger = $logger;
        $this->maxRetries = $maxRetries;
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
            $events = array_merge($this->events, $this->fallback->restore());
            $this->write($events);
            $this->events = [];
        } catch (Exception $e) {
            $this->fallback->store($this->events);
            $this->monitor(false);
            $this->logger->error("Exception while writing to the queue", ['exception' => $e->getMessage()]);
        }
    }

    /**
     * @param array $events
     *
     * @throws QueueException
     */
    private function write(array $events)
    {
        foreach ($this->writers as $writer) {
            if ($this->writeAndRetry($writer, $events)) {
                $this->monitor(true, get_class($writer));
                return;
            }
        }

        throw new QueueException("Impossible to write to the queue");
    }

    /**
     * Tries to write into the queue and retries if failed.
     *
     * @param QueueWriter   $writer
     * @param DomainEvent[] $events
     * @param int           $attempts
     *
     * @return bool
     */
    private function writeAndRetry(QueueWriter $writer, array $events, $attempts = 0): bool
    {
        if ($attempts > $this->maxRetries) {
            return false;
        }

        try {
            $writer->write($events);
            return true;
        } catch (QueueException $e) {
            return $this->writeAndRetry($writer, $events, $attempts+1);
        }
    }

    /**
     * @param bool   $success
     * @param string $writer
     */
    private function monitor(bool $success, string $writer = 'none')
    {
        foreach ($this->events as $event) {
            $this->monitor->increment('domain_events.publish', [
                'name'    => $event->name(),
                'success' => $success,
                'writer'  => $writer,
            ]);
        }
    }
}
