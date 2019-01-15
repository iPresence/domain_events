<?php

namespace IPresence\DomainEvents\Queue\RabbitMQ;

use IPresence\DomainEvents\Queue\Exception\ReaderException;
use IPresence\DomainEvents\Queue\Exception\TimeoutException;
use IPresence\DomainEvents\Queue\QueueReader;
use IPresence\DomainEvents\Queue\RabbitMQ\Consumer\RabbitMQConsumer;
use IPresence\DomainEvents\Queue\RabbitMQ\Exchange\RabbitMQExchange;
use IPresence\DomainEvents\Queue\RabbitMQ\Queue\RabbitMQQueue;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use Psr\Log\LoggerInterface;

class RabbitMQReader implements QueueReader
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
    protected $logger;

    /**
     * @var string
     */
    protected $consumerTag = '';

    /**
     * @param RabbitMQExchange $exchange
     * @param RabbitMQQueue    $queue
     * @param RabbitMQConsumer $consumer
     * @param LoggerInterface  $logger
     */
    public function __construct(
        RabbitMQExchange $exchange,
        RabbitMQQueue $queue,
        RabbitMQConsumer $consumer,
        LoggerInterface $logger
    ) {
        $this->exchange = $exchange;
        $this->queue = $queue;
        $this->consumer = $consumer;
        $this->logger = $logger;
    }

    /**
     * @param callable $callback
     * @param int      $timeout
     *
     * @throws TimeoutException
     * @throws ReaderException
     */
    public function read(callable $callback, $timeout = 0)
    {
        $queue = $this->initialize();

        try {
            $this->consumer->start($queue, $callback, $timeout);
        } catch(AMQPTimeoutException $e) {
            $this->consumer->stop();
            $this->logger->error('Timeout when consuming events', ['exception' => $e->getMessage()]);
            throw new TimeoutException("Timed out at $timeout seconds while reading", 0, $e);
        } catch(\Exception $e) {
            $this->consumer->stop();
            $this->logger->error('Error while consuming events', ['exception' => $e->getMessage()]);
            throw new ReaderException($e->getMessage(), $e->getCode());
        }
    }

    /**
     * @return string
     * @throws ReaderException
     */
    protected function initialize(): string
    {
        try {
            $name = $this->exchange->create();
            return $this->queue->createAndBindTo($name);
        } catch (\Exception $e) {
            $this->logger->error('Error creating the exchange or the queue', ['exception' => $e->getMessage()]);
            throw new ReaderException($e->getMessage(), $e->getCode());
        }
    }
}
