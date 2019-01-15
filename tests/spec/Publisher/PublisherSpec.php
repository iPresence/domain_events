<?php

namespace spec\IPresence\DomainEvents\Publisher;

use IPresence\DomainEvents\DomainEvent;
use IPresence\DomainEvents\Publisher\Publisher;
use IPresence\DomainEvents\Queue\QueueWriter;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;

/**
 * @mixin Publisher
 */
class PublisherSpec extends ObjectBehavior
{
    public function let(QueueWriter $writer, LoggerInterface $logger)
    {
        $this->beConstructedWith($writer, $logger, 3);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('IPresence\DomainEvents\Publisher\Publisher');
    }

    public function it_does_not_call_the_writer_if_there_are_no_events(QueueWriter $writer)
    {
        $writer->write(Argument::cetera())->shouldNotBeCalled();
        $this->publish();
    }

    public function it_writes_the_added_events(QueueWriter $writer, DomainEvent $event1, DomainEvent $event2)
    {
        $writer->write([$event1, $event2])->shouldBeCalled();

        $this->add($event1);
        $this->add($event2);
        $this->publish();
    }

    public function it_retries(QueueWriter $writer, LoggerInterface $logger, DomainEvent $event)
    {
        $logger->error(Argument::any())->shouldBeCalled();
        $writer->write([$event])->willThrow(new \Exception());

        $this->add($event);
        $this->publish();
    }
}
