<?php

namespace IPresence\DomainEvents\Queue\RabbitMQ;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPLazyConnection;

class RabbitMQLazyConnection
{
    /**
     * @var AMQPLazyConnection
     */
    private $connection;

    /**
     * @var AMQPChannel
     */
    private $channel;

    /**
     * @param AMQPLazyConnection $connection
     */
    public function __construct(AMQPLazyConnection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * It always return the same channel in oder to improve the performance and reduce the number of connections to rabbit.
     *
     * @return AMQPChannel
     */
    public function channel(): AMQPChannel
    {
        if (!$this->channel) {
            $this->channel = $this->connection->channel();
        }

        return $this->channel;
    }
}
