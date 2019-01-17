<?php declare(strict_types=1);

namespace IPresence\DomainEvents\Queue\RabbitMQ\Queue;

use IPresence\DomainEvents\Queue\RabbitMQ\RabbitMQLazyConnection;

class RabbitMQQueue
{
    /**
     * @var RabbitMQLazyConnection
     */
    private $connection;

    /**
     * @var RabbitMQQueueConfig
     */
    private $config;

    /**
     * @param RabbitMQLazyConnection $connection
     * @param RabbitMQQueueConfig    $config
     */
    public function __construct(RabbitMQLazyConnection $connection, RabbitMQQueueConfig $config)
    {
        $this->connection = $connection;
        $this->config = $config;
    }

    /**
     * @param string $exchange
     *
     * @return string
     */
    public function createAndBindTo(string $exchange): string
    {
        $this->createQueue();
        $this->bindQueue($exchange);

        return $this->config->name();
    }

    /**
     * Creates the queue
     */
    private function createQueue()
    {
        $channel = $this->connection->channel();
        $channel->queue_declare(
            $this->config->name(),
            $this->config->passive(),
            $this->config->durable(),
            $this->config->exclusive(),
            $this->config->autoDelete()
        );
    }

    /**
     * Bind the queue to the exchange
     *
     * @param string $exchange
     */
    protected function bindQueue(string $exchange)
    {
        $channel = $this->connection->channel();
        $channel->queue_bind($this->config->name(), $exchange);
        foreach ($this->config->bindings() as $biding) {
            $channel->queue_bind($this->config->name(), $exchange, $biding);
        }
    }
}
