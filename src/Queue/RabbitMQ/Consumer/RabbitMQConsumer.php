<?php declare(strict_types=1);

namespace IPresence\DomainEvents\Queue\RabbitMQ\Consumer;

use IPresence\DomainEvents\Queue\RabbitMQ\RabbitMQLazyConnection;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitMQConsumer
{
    /**
     * @var RabbitMQLazyConnection
     */
    private $connection;

    /**
     * @var RabbitMQConsumerConfig
     */
    private $config;

    /**
     * @param RabbitMQLazyConnection $connection
     * @param RabbitMQConsumerConfig $config
     */
    public function __construct(RabbitMQLazyConnection $connection, RabbitMQConsumerConfig $config)
    {
        $this->connection = $connection;
        $this->config = $config;
    }

    /**
     * Read messages from the queue
     *
     * @param string   $queue
     * @param callable $callback
     *
     * @return int
     */
    public function get(string $queue, callable $callback): int
    {
        $channel = $this->connection->channel();

        $callable = function(AMQPMessage $message) use ($callback, $channel) {
            try {
                call_user_func($callback, $message->body);
                $channel->basic_ack($message->delivery_info['delivery_tag']);
            } catch (\Throwable $exception) {
                $channel->basic_nack($message->delivery_info['delivery_tag']);
            }
        };

        $message = $channel->basic_get(
            $queue,
            $this->config->noAck()
        );

        if (null === $message) {
            return 0;
        }

        $callable($message);

        return 1;
    }
}
