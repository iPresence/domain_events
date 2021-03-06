<?php

namespace spec\IPresence\DomainEvents\Queue\RabbitMQ;

use Exception;
use IPresence\DomainEvents\DomainEvent;
use IPresence\DomainEvents\Queue\Exception\QueueException;
use IPresence\DomainEvents\Queue\Exception\StopReadingException;
use IPresence\DomainEvents\Queue\RabbitMQ\Consumer\RabbitMQConsumer as Consumer;
use IPresence\DomainEvents\Queue\RabbitMQ\Exchange\RabbitMQExchange as Exchange;
use IPresence\DomainEvents\Queue\RabbitMQ\Queue\RabbitMQQueue as Queue;
use IPresence\DomainEvents\Queue\RabbitMQ\RabbitMQQueue;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;

/**
 * @mixin RabbitMQQueue
 */
class RabbitMQQueueSpec extends ObjectBehavior
{
    public function let(
        Exchange $exchange,
        Queue $queue,
        Consumer $consumer,
        LoggerInterface $logger
    ) {
        $this->beConstructedWith($exchange, $queue, $consumer, $logger);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('IPresence\DomainEvents\Queue\RabbitMQ\RabbitMQQueue');
    }

    public function it_throws_an_exception_if_can_not_create_the_exchange(
        Exchange $exchange,
        LoggerInterface $logger
    ) {
        $logger->error(Argument::cetera())->shouldBeCalled();
        $exchange->create()->willThrow(new Exception());
        $this->shouldThrow(new QueueException())->duringRead(function(){});
    }

    public function it_throws_an_exception_if_can_not_create_or_bind_the_queue(
        Exchange $exchange,
        Queue $queue,
        LoggerInterface $logger
    ) {
        $logger->error(Argument::cetera())->shouldBeCalled();
        $exchange->create()->willReturn('exchanged');
        $queue->createAndBindTo('exchanged')->willThrow(new Exception());
        $this->shouldThrow(new QueueException())->duringRead(function(){});
    }

    public function it_throws_an_exception_if_can_not_start_the_consumer(
        Exchange $exchange,
        Queue $queue,
        Consumer $consumer,
        LoggerInterface $logger
    ) {
        $callable = function(){};

        $logger->debug("Started reading events from RabbitMQ")->shouldBeCalled();
        $logger->error(Argument::cetera())->shouldBeCalled();
        $exchange->create()->willReturn('exchanged');
        $queue->createAndBindTo('exchanged')->willReturn('queue');
        $consumer->get('queue', $callable)->willThrow(new Exception());

        $this->shouldThrow(new QueueException())->duringRead($callable);
    }

    public function it_get_messages(Exchange $exchange, Queue $queue, Consumer $consumer)
    {
        $callable = function(){};

        $exchange->create()->willReturn('exchanged');
        $queue->createAndBindTo('exchanged')->willReturn('queue');
        $consumer->get('queue', $callable)->shouldBeCalled();

        $this->read($callable, 2);
    }

    public function it_throws_an_exception_if_can_not_create_the_exchanged(
        Exchange $exchange,
        LoggerInterface $logger,
        DomainEvent $event
    ) {
        $exchange->create()->willThrow(new \Exception());
        $exchange->publish(Argument::any())->shouldNotBeCalled();
        $logger->error(Argument::cetera())->shouldBeCalled();

        $this->shouldThrow(new QueueException())->duringWrite([$event]);
    }

    public function it_throws_an_exception_if_can_not_publish_the_exchanged(
        Exchange $exchange,
        LoggerInterface $logger,
        DomainEvent $event
    ) {
        $events = [$event];

        $exchange->create()->shouldBeCalled();
        $exchange->publish($events)->willThrow(new \Exception());
        $logger->error(Argument::cetera())->shouldBeCalled();

        $this->shouldThrow(new QueueException())->duringWrite($events);
    }

    public function it_published_the_events(Exchange $exchange, DomainEvent $event)
    {
        $events = [$event];

        $exchange->create()->shouldBeCalled();
        $exchange->publish($events)->shouldBeCalled();
        $this->write($events);
    }
}
