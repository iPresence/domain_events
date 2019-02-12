<?php

namespace spec\IPresence\DomainEvents\Publisher;

use IPresence\DomainEvents\DomainEvent;
use IPresence\DomainEvents\Publisher\Fallback\PublisherFallback;
use IPresence\DomainEvents\Publisher\Publisher;
use IPresence\DomainEvents\Queue\Exception\QueueException;
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
    public function let(
        QueueWriter $writer1,
        QueueWriter $writer2,
        PublisherFallback $fallback,
        Monitor $monitor,
        LoggerInterface $logger
    ) {
        $this->beConstructedWith([$writer1, $writer2], $fallback, $monitor, $logger, 3);
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
        QueueWriter $writer1,
        PublisherFallback $fallback,
        Monitor $monitor,
        DomainEvent $event1,
        DomainEvent $event2
    ) {
        $event1->name()->willReturn('name1');
        $event2->name()->willReturn('name2');

        $fallback->restore()->willReturn([]);
        $fallback->store(Argument::cetera())->shouldNotBeCalled();

        $writer1->write([$event1, $event2])->shouldBeCalled();
        $monitor->increment('domain_events.publish', ['name' => 'name1', 'success' => true, 'writer' => get_class($writer1->getWrappedObject())])->shouldBeCalled();
        $monitor->increment('domain_events.publish', ['name' => 'name2', 'success' => true, 'writer' => get_class($writer1->getWrappedObject())])->shouldBeCalled();

        $this->add($event1);
        $this->add($event2);
        $this->publish();
    }

    public function it_writes_on_the_second_writer_if_the_first_fail(
        QueueWriter $writer1,
        QueueWriter $writer2,
        PublisherFallback $fallback,
        Monitor $monitor,
        DomainEvent $event
    ) {
        $event->name()->willReturn('name');

        $fallback->restore()->willReturn([]);
        $fallback->store(Argument::cetera())->shouldNotBeCalled();

        $writer1->write([$event])->willThrow(new QueueException('test'));
        $writer2->write([$event])->shouldBeCalled();
        $monitor->increment('domain_events.publish', ['name' => 'name', 'success' => true, 'writer' => get_class($writer2->getWrappedObject())])->shouldBeCalled();

        $this->add($event);
        $this->publish();
    }

    public function it_writes_in_the_fallback_if_can_not_Write_in_any_writer(
        QueueWriter $writer1,
        QueueWriter $writer2,
        PublisherFallback $fallback,
        Monitor $monitor,
        LoggerInterface $logger,
        DomainEvent $event
    ) {
        $event->name()->willReturn('name');

        $fallback->restore()->willReturn([]);
        $fallback->store([$event])->shouldBeCalled();

        $logger->error('Exception while writing to the queue', ['exception' => 'test'])->shouldBeCalled();
        $writer1->write([$event])->willThrow(new \Exception('test'));
        $writer2->write([$event])->willThrow(new \Exception('test'));
        $monitor->increment('domain_events.publish', ['name' => 'name', 'success' => false, 'writer' => 'none'])->shouldBeCalled();

        $this->add($event);
        $this->publish();
    }
}
