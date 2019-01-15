<?php

namespace spec\IPresence\DomainEvents\Queue\RabbitMQ;

use Exception;
use IPresence\DomainEvents\Queue\Exception\ReaderException;
use IPresence\DomainEvents\Queue\Exception\TimeoutException;
use IPresence\DomainEvents\Queue\RabbitMQ\Consumer\RabbitMQConsumer;
use IPresence\DomainEvents\Queue\RabbitMQ\Exchange\RabbitMQExchange;
use IPresence\DomainEvents\Queue\RabbitMQ\Queue\RabbitMQQueue;
use IPresence\DomainEvents\Queue\RabbitMQ\RabbitMQReader;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;

/**
 * @mixin RabbitMQReader
 */
class RabbitMQReaderSpec extends ObjectBehavior
{
    public function let(
        RabbitMQExchange $exchange,
        RabbitMQQueue $queue,
        RabbitMQConsumer $consumer,
        LoggerInterface $logger
    ) {
        $this->beConstructedWith($exchange, $queue, $consumer, $logger);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('IPresence\DomainEvents\Queue\RabbitMQ\RabbitMQReader');
    }

    public function it_throws_an_exception_if_can_not_create_the_exchange(
        RabbitMQExchange $exchange,
        LoggerInterface $logger
    ) {
        $logger->error(Argument::cetera())->shouldBeCalled();
        $exchange->create()->willThrow(new Exception());
        $this->shouldThrow(new ReaderException())->duringRead(function(){}, 0);
    }

    public function it_throws_an_exception_if_can_not_create_or_bind_the_queue(
        RabbitMQExchange $exchange,
        RabbitMQQueue $queue,
        LoggerInterface $logger
    ) {
        $logger->error(Argument::cetera())->shouldBeCalled();
        $exchange->create()->willReturn('exchanged');
        $queue->createAndBindTo('exchanged')->willThrow(new Exception());
        $this->shouldThrow(new ReaderException())->duringRead(function(){}, 0);
    }

    public function it_throws_an_exception_if_can_not_start_the_consumer(
        RabbitMQExchange $exchange,
        RabbitMQQueue $queue,
        RabbitMQConsumer $consumer,
        LoggerInterface $logger
    ) {
        $callable = function(){};

        $logger->error(Argument::cetera())->shouldBeCalled();
        $exchange->create()->willReturn('exchanged');
        $queue->createAndBindTo('exchanged')->willReturn('queue');
        $consumer->start('queue', $callable, 0)->willThrow(new Exception());
        $consumer->stop()->shouldBeCalled();

        $this->shouldThrow(new ReaderException())->duringRead($callable, 0);
    }

    public function it_stops_the_consumer_if_there_is_a_timeout(
        RabbitMQExchange $exchange,
        RabbitMQQueue $queue,
        RabbitMQConsumer $consumer,
        LoggerInterface $logger
    ) {
        $callable = function(){};
        $exception = new AMQPTimeoutException();

        $logger->error(Argument::cetera())->shouldBeCalled();
        $exchange->create()->willReturn('exchanged');
        $queue->createAndBindTo('exchanged')->willReturn('queue');
        $consumer->start('queue', $callable, 2)->willThrow($exception);
        $consumer->stop()->shouldBeCalled();

        $this->shouldThrow(TimeoutException::class)->duringRead($callable, 2);
    }

    public function it_consumes(
        RabbitMQExchange $exchange,
        RabbitMQQueue $queue,
        RabbitMQConsumer $consumer
    ) {
        $callable = function(){};

        $exchange->create()->willReturn('exchanged');
        $queue->createAndBindTo('exchanged')->willReturn('queue');
        $consumer->start('queue', $callable, 2)->shouldBeCalled();
        $consumer->stop()->shouldNotBeCalled();

        $this->read($callable, 2);
    }
}
