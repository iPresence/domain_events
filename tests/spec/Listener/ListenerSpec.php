<?php

namespace spec\IPresence\DomainEvents\Listener;

use IPresence\DomainEvents\DomainEvent;
use IPresence\DomainEvents\Listener\DomainEventSubscriber;
use IPresence\DomainEvents\Listener\Listener;
use IPresence\DomainEvents\Queue\QueueReader;
use PhpSpec\ObjectBehavior;
use Psr\Log\LoggerInterface;

/**
 * @mixin Listener
 */
class ListenerSpec extends ObjectBehavior
{
    public function let(QueueReader $reader, LoggerInterface $logger)
    {
        $this->beConstructedWith($reader, $logger);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('IPresence\DomainEvents\Listener\Listener');
    }

    public function it_notifies_the_subscribers(DomainEventSubscriber $subscriber, DomainEvent $event)
    {
        $event->name()->willReturn('name');
        $subscriber->isSubscribed($event)->shouldBeCalled();

        $this->subscribe($subscriber);
        $this->notify($event);
    }

    public function it_executes_just_the_subscribed_subscribers(
        DomainEventSubscriber $subscriber1,
        DomainEventSubscriber $subscriber2,
        DomainEventSubscriber $subscriber3,
        DomainEvent $event
    ) {
        $event->name()->willReturn('name');

        $subscriber1->isSubscribed($event)->willReturn(true);
        $subscriber2->isSubscribed($event)->willReturn(false);
        $subscriber3->isSubscribed($event)->willReturn(true);

        $subscriber1->execute($event)->shouldBeCalled();
        $subscriber2->execute($event)->shouldNotBeCalled();
        $subscriber3->execute($event)->shouldBeCalled();

        $this->subscribe($subscriber1);
        $this->subscribe($subscriber2);
        $this->subscribe($subscriber3);

        $this->notify($event);
    }
}
