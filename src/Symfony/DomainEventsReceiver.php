<?php declare(strict_types=1);

namespace IPresence\DomainEvents\Symfony;

use IPresence\DomainEvents\Listener\Listener;
use Symfony\Component\Messenger\Transport\ReceiverInterface;

class DomainEventsReceiver implements ReceiverInterface
{
    /**
     * @var
     */
    private $shouldStop;

    /**
     * @var Listener
     */
    private $listener;

    /**
     * DomainEventReceiver constructor.
     *
     * @param Listener $listener
     */
    public function __construct(Listener $listener)
    {
        $this->listener = $listener;
    }

    /**
     * Receive some messages to the given handler.
     *
     * The handler will have, as argument, the received {@link \Symfony\Component\Messenger\Envelope} containing the message.
     * Note that this envelope can be `null` if the timeout to receive something has expired.
     *
     * @param callable $handler
     */
    public function receive(callable $handler): void
    {
        while (!$this->shouldStop) {
            $this->listener->listen();

            // Executes the Symfony receiver middleware
            $handler(null);
        }
    }

    /**
     * Stops the receiver
     */
    public function stop(): void
    {
        $this->shouldStop = true;
    }
}