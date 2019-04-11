<?php

namespace spec\IPresence\DomainEvents\Listener;

use IPresence\DomainEvents\DomainEvent;
use IPresence\DomainEvents\DomainEventFactory;
use IPresence\DomainEvents\Listener\DomainEventSubscriber;
use IPresence\DomainEvents\Listener\Listener;
use IPresence\DomainEvents\Queue\Exception\StopReadingException;
use IPresence\DomainEvents\Queue\QueueReader;
use IPresence\Monitoring\Monitor;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;

/**
 * @mixin Listener
 */
class ListenerSpec extends ObjectBehavior
{
    public function let(
        QueueReader $reader1,
        QueueReader $reader2,
        DomainEventFactory $factory,
        Monitor $monitor,
        LoggerInterface $logger
    ) {
        $this->beConstructedWith([$reader1, $reader2], $factory, $monitor, $logger);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('IPresence\DomainEvents\Listener\Listener');
    }

    public function it_reads_messages_from_all_the_readers(
        QueueReader $reader1,
        QueueReader $reader2
    ) {
        $reader1->read(Argument::type('callable'))->willReturn(1)->shouldBeCalled();
        $reader2->read(Argument::type('callable'))->willReturn(3)->shouldBeCalled();

        $this->listen();
    }

    public function it_handles_exceptions_in_readers(QueueReader $reader1, LoggerInterface $logger)
    {
        $exception = new \Exception("foo");

        $reader1->read(Argument::type('callable'))->willThrow($exception);

        $this->listen();

        $logger->error('Unexpected exception while reading events', ['exception' => $exception])->shouldHaveBeenCalled();
    }

    public function it_notifies_the_subscribers(
        DomainEventSubscriber $subscriber,
        DomainEventFactory $factory,
        Monitor $monitor,
        DomainEvent $event
    ) {
        $json = 'json';
        $factory->fromJSON($json)->willReturn($event);

        $event->name()->willReturn('name');
        $monitor->increment('domain_event.received', ['name' => 'name'])->shouldBeCalled();
        $subscriber->isSubscribed($event)->shouldBeCalled();

        $this->subscribe($subscriber);
        $this->notify($json);
    }

    public function it_executes_just_the_subscribed_subscribers(
        DomainEventFactory $factory,
        Monitor $monitor,
        DomainEventSubscriber $subscriber1,
        DomainEventSubscriber $subscriber2,
        DomainEventSubscriber $subscriber3,
        DomainEvent $event
    ) {
        $json = 'json';

        $factory->fromJSON($json)->willReturn($event);
        $event->name()->willReturn('test');

        $monitor->increment('domain_event.received', ['name' => 'test'])->shouldBeCalled();
        $monitor->start('domain_event.consumed', ['name' => 'test', 'subscriber' => get_class($subscriber1->getWrappedObject())])->shouldBeCalled();
        $monitor->start('domain_event.consumed', ['name' => 'test', 'subscriber' => get_class($subscriber3->getWrappedObject())])->shouldBeCalled();
        $monitor->end('domain_event.consumed', ['success' => true])->shouldBeCalled();

        $subscriber1->isSubscribed($event)->willReturn(true);
        $subscriber2->isSubscribed($event)->willReturn(false);
        $subscriber3->isSubscribed($event)->willReturn(true);

        $subscriber1->execute($event)->shouldBeCalled();
        $subscriber2->execute($event)->shouldNotBeCalled();
        $subscriber3->execute($event)->shouldBeCalled();

        $this->subscribe($subscriber1);
        $this->subscribe($subscriber2);
        $this->subscribe($subscriber3);

        $this->notify($json);
    }
}
