<?php

namespace spec\IPresence\DomainEvents\Queue\RabbitMQ;

use IPresence\DomainEvents\DomainEvent;
use IPresence\DomainEvents\Queue\Exception\WriterException;
use IPresence\DomainEvents\Queue\RabbitMQ\Exchange\RabbitMQExchange;
use IPresence\DomainEvents\Queue\RabbitMQ\RabbitMQWriter;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;

/**
 * @mixin RabbitMQWriter
 */
class RabbitMQWriterSpec extends ObjectBehavior
{
    public function let(RabbitMQExchange $exchange, LoggerInterface $logger)
    {
        $this->beConstructedWith($exchange, $logger);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('IPresence\DomainEvents\Queue\RabbitMQ\RabbitMQWriter');
    }

    public function it_throws_an_exception_if_can_not_create_the_exchanged(
        RabbitMQExchange $exchange,
        LoggerInterface $logger,
        DomainEvent $event
    ) {
        $exchange->create()->willThrow(new \Exception());
        $exchange->publish(Argument::any())->shouldNotBeCalled();
        $logger->error(Argument::cetera())->shouldBeCalled();

        $this->shouldThrow(new WriterException())->duringWrite([$event]);
    }

    public function it_throws_an_exception_if_can_not_publish_the_exchanged(
        RabbitMQExchange $exchange,
        LoggerInterface $logger,
        DomainEvent $event
    ) {
        $events = [$event];

        $exchange->create()->shouldBeCalled();
        $exchange->publish($events)->willThrow(new \Exception());
        $logger->error(Argument::cetera())->shouldBeCalled();

        $this->shouldThrow(new WriterException())->duringWrite($events);
    }

    public function it_published_the_events(
        RabbitMQExchange $exchange,
        DomainEvent $event
    ) {
        $events = [$event];

        $exchange->create()->shouldBeCalled();
        $exchange->publish($events)->shouldBeCalled();
        $this->write($events);
    }
}
