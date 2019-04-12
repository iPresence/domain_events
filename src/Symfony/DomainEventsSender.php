<?php declare(strict_types=1);

namespace IPresence\DomainEvents\Symfony;

use IPresence\DomainEvents\DomainEvent;
use IPresence\DomainEvents\Publisher\Publisher;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\SenderInterface;

/**
 * Send domain events from symfony messenger component
 */
class DomainEventsSender implements SenderInterface
{
    /**
     * @var Publisher
     */
    private $publisher;

    /**
     * @param Publisher $publisher
     */
    public function __construct(Publisher $publisher)
    {
        $this->publisher = $publisher;
    }

    /**
     * Sends the given envelope.
     *
     * @param Envelope $envelope
     */
    public function send(Envelope $envelope)
    {
        $message = $envelope->getMessage();

        if ($message instanceof DomainEvent) {
            $this->publisher->add($message);
            $this->publisher->publish();
        }
    }
}
