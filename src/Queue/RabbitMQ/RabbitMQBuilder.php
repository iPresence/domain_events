<?php

namespace IPresence\DomainEvents\Queue\RabbitMQ;

use IPresence\DomainEvents\DomainEventFactory;
use IPresence\DomainEvents\Queue\RabbitMQ\Consumer\RabbitMQConsumer;
use IPresence\DomainEvents\Queue\RabbitMQ\Consumer\RabbitMQConsumerConfig;
use IPresence\DomainEvents\Queue\RabbitMQ\Exchange\RabbitMQExchange;
use IPresence\DomainEvents\Queue\RabbitMQ\Exchange\RabbitMQExchangeConfig;
use IPresence\DomainEvents\Queue\RabbitMQ\Queue\RabbitMQQueue;
use IPresence\DomainEvents\Queue\RabbitMQ\Queue\RabbitMQQueueConfig;
use PhpAmqpLib\Connection\AMQPLazyConnection;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RuntimeException;

class RabbitMQBuilder
{
    /**
     * @var RabbitMQExchange
     */
    private $exchange;

    /**
     * @var RabbitMQQueue
     */
    private $queue;

    /**
     * @var RabbitMQConsumer
     */
    private $consumer;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @return RabbitMQBuilder
     */
    public static function create(): self
    {
        return new self();
    }

    /**
     * @param array $config
     *
     * @return $this
     */
    public function withConfig(array $config)
    {
        $connection = new RabbitMQLazyConnection(
            new AMQPLazyConnection(
                $this->getParamOrFail($config, 'host'),
                $this->getParamOrFail($config, 'port'),
                $this->getParamOrFail($config, 'user'),
                $this->getParamOrFail($config, 'pass'),
                $this->getParam($config, 'vhost', '/')
            )
        );

        $exchange = $this->getParamOrFail($config, 'exchange');
        $this->exchange = new RabbitMQExchange($connection, new RabbitMQExchangeConfig(
            $this->getParamOrFail($exchange, 'name'),
            $this->getParam($exchange, 'type', 'direct'),
            $this->getParam($exchange, 'passive', false),
            $this->getParam($exchange, 'durable', true),
            $this->getParam($exchange, 'autoDelete', false)
        ));

        $queue = $this->getParamOrFail($config, 'queue');
        $this->queue = new RabbitMQQueue($connection, new RabbitMQQueueConfig(
            $this->getParamOrFail($queue, 'name'),
            $this->getParam($queue, 'bindings', []),
            $this->getParam($queue, 'passive', false),
            $this->getParam($queue, 'durable', true),
            $this->getParam($queue, 'exclusive', false),
            $this->getParam($queue, 'autoDelete', false)
        ));

        $factory = new DomainEventFactory($this->getParam($config, 'mapping', []));

        $consumer = $this->getParam($config, 'consumer', []);
        $this->consumer = new RabbitMQConsumer($connection, $factory, new RabbitMQConsumerConfig(
            $this->getParam($consumer, 'noLocal', false),
            $this->getParam($consumer, 'noAck', false),
            $this->getParam($consumer, 'exclusive', false),
            $this->getParam($consumer, 'noWait', false)
        ));

        return $this;
    }

    /**
     * @param LoggerInterface $logger
     *
     * @return $this
     */
    public function withLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * @return RabbitMQWriter
     */
    public function buildWriter(): RabbitMQWriter
    {
        if (!$this->exchange) {
            throw new RuntimeException("You need to provide a configuration");
        }

        if (!$this->logger) {
            $this->logger = new NullLogger();
        }

        return new RabbitMQWriter($this->exchange, $this->logger);
    }

    /**
     * @return RabbitMQReader
     */
    public function buildReader(): RabbitMQReader
    {
        if (!$this->exchange) {
            throw new RuntimeException("You need to provide a configuration");
        }
        if (!$this->queue) {
            throw new RuntimeException("You need to provide a configuration");
        }
        if (!$this->consumer) {
            throw new RuntimeException("You need to provide a configuration");
        }

        if (!$this->logger) {
            $this->logger = new NullLogger();
        }

        return new RabbitMQReader($this->exchange, $this->queue, $this->consumer, $this->logger);
    }

    /**
     * @param array  $config
     * @param string $param
     * @param mixed  $default
     *
     * @return mixed
     */
    private function getParam(array $config, string $param, $default = null)
    {
        return isset($config[$param])  ? $config[$param] : $default;
    }

    /**
     * @param array  $config
     * @param string $param
     *
     * @return mixed
     */
    private function getParamOrFail(array $config, string $param)
    {
        if (!isset($config[$param])) {
            throw new RuntimeException("The configuration is missing the required parameter: $param");
        }

        return $config[$param];
    }
}
