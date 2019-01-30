<?php

namespace spec\IPresence\DomainEvents\Publisher;

use IPresence\DomainEvents\DomainEvent;
use IPresence\DomainEvents\Publisher\Fallback\PublisherFallback;
use IPresence\DomainEvents\Publisher\Publisher;
use IPresence\DomainEvents\Queue\QueueWriter;
use IPresence\Monitoring\Monitor;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;

/**
 * @mixin Publisher
 */
class PublisherSpec extends ObjectBehavior
{
    public function let(QueueWriter $writer, PublisherFallback $fallback, Monitor $monitor, LoggerInterface $logger)
    {
        $this->beConstructedWith($writer, $fallback, $monitor, $logger, 3);
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

    public function it_writes_the_added_events(
        QueueWriter $writer,
        PublisherFallback $fallback,
        Monitor $monitor,
        DomainEvent $event1,
        DomainEvent $event2
    ) {
        $event1->name()->willReturn('name1');
        $event2->name()->willReturn('name2');

        $fallback->restore()->willReturn([]);
        $fallback->store(Argument::cetera())->shouldNotBeCalled();

        $writer->write([$event1, $event2])->shouldBeCalled();
        $monitor->increment('domain_events.publish', ['name' => 'name1', 'success' => true])->shouldBeCalled();
        $monitor->increment('domain_events.publish', ['name' => 'name2', 'success' => true])->shouldBeCalled();

        $this->add($event1);
        $this->add($event2);
        $this->publish();
    }

    public function it_retries(
        QueueWriter $writer,
        PublisherFallback $fallback,
        Monitor $monitor,
        LoggerInterface $logger,
        DomainEvent $event
    ) {
        $event->name()->willReturn('name');

        $fallback->restore()->willReturn([]);
        $fallback->store([$event])->shouldBeCalled();

        $logger->error('Exception while writing to the queue', ['exception' => 'test'])->shouldBeCalled();
        $writer->write([$event])->willThrow(new \Exception('test'));
        $monitor->increment('domain_events.publish', ['name' => 'name', 'success' => false])->shouldBeCalled();

        $this->add($event);
        $this->publish();
    }
}
