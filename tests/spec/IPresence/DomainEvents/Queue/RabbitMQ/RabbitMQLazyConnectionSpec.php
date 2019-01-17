<?php

namespace spec\IPresence\DomainEvents\Queue\RabbitMQ;

use IPresence\DomainEvents\Queue\RabbitMQ\RabbitMQLazyConnection;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPLazyConnection;
use PhpSpec\ObjectBehavior;

/**
 * @mixin RabbitMQLazyConnection
 */
class RabbitMQLazyConnectionSpec extends ObjectBehavior
{
    public function let(AMQPLazyConnection $connection)
    {
        $this->beConstructedWith($connection);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('IPresence\DomainEvents\Queue\RabbitMQ\RabbitMQLazyConnection');
    }

    public function it_returns_a_channel(AMQPLazyConnection $connection, AMQPChannel $channel)
    {
        $connection->channel()->willReturn($channel);
        $this->channel()->shouldBe($channel);
    }
}
