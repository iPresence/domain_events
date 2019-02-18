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
     * @var string
     */
    private $consumerTag = '';

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
     * Starts consuming from a given queue
     *
     * @param string   $queue
     * @param callable $callback
     * @param int      $timeout
     *
     * @throws \ErrorException
     */
    public function start(string $queue, callable $callback, int $timeout)
    {
        $channel = $this->connection->channel();
        $callable = function(AMQPMessage $message) use ($callback) {
            call_user_func($callback, $message->body);
            $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);
        };

        if ($this->consumerTag === '') {
            $this->consumerTag = $channel->basic_consume(
                $queue,
                '',
                $this->config->noLocal(),
                $this->config->noAck(),
                $this->config->exclusive(),
                $this->config->noWait(),
                $callable
            );
        }

        while (count($channel->callbacks)) {
            $channel->wait(null, false , $timeout);
        }
    }

    /**
     * Stops consuming
     */
    public function stop()
    {
        if ($this->consumerTag) {
            try {
                $channel = $this->connection->channel();
                $channel->basic_cancel($this->consumerTag);
            } catch(\Exception $e) {
                // Nothing to do here
            }
            $this->consumerTag = '';
        }
    }
}