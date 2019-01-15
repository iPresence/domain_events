<?php

namespace IPresence\DomainEvents\Queue\RabbitMQ\Exchange;

use InvalidArgumentException;
use IPresence\DomainEvents\DomainEvent;
use IPresence\DomainEvents\Queue\RabbitMQ\RabbitMQLazyConnection;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitMQExchange
{
    /**
     * @var RabbitMQLazyConnection
     */
    private $connection;

    /**
     * @var RabbitMQExchangeConfig
     */
    private $config;

    /**
     * @param RabbitMQLazyConnection $connection
     * @param RabbitMQExchangeConfig $config
     */
    public function __construct(RabbitMQLazyConnection $connection, RabbitMQExchangeConfig $config)
    {
        $this->connection = $connection;
        $this->config = $config;
    }

    /**
     * Creates the exchange
     */
    public function create(): string
    {
        $channel = $this->connection->channel();
        $channel->exchange_declare(
            $this->config->name(),
            $this->config->type(),
            $this->config->passive(),
            $this->config->durable(),
            $this->config->autoDelete()
        );

        return $this->config->name();
    }

    /**
     * @param DomainEvent[] $events
     */
    public function publish(array $events)
    {
        $channel = $this->connection->channel();
        foreach($events as $event) {
            $json = json_encode($event);
            if (json_last_error() != JSON_ERROR_NONE) {
                throw new InvalidArgumentException(json_last_error_msg());
            }

            $msg = new AMQPMessage($json, ['delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT]);
            $channel->batch_basic_publish($msg, $this->config->name(), $event->name());
        }

        $channel->publish_batch();
    }
}
