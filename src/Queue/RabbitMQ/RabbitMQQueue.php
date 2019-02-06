<?php declare(strict_types=1);

namespace IPresence\DomainEvents\Queue\RabbitMQ;

use Exception;
use IPresence\DomainEvents\DomainEvent;
use IPresence\DomainEvents\Queue\Exception\QueueException;
use IPresence\DomainEvents\Queue\Exception\StopReadingException;
use IPresence\DomainEvents\Queue\QueueReader;
use IPresence\DomainEvents\Queue\QueueWriter;
use IPresence\DomainEvents\Queue\RabbitMQ\Consumer\RabbitMQConsumer as Consumer;
use IPresence\DomainEvents\Queue\RabbitMQ\Exchange\RabbitMQExchange as Exchange;
use IPresence\DomainEvents\Queue\RabbitMQ\Queue\RabbitMQQueue as Queue;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use Psr\Log\LoggerInterface;

class RabbitMQQueue implements QueueReader, QueueWriter
{
    /**
     * @var Exchange
     */
    private $exchange;

    /**
     * @var Queue
     */
    private $queue;

    /**
     * @var Consumer
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
     * @param Exchange        $exchange
     * @param Queue           $queue
     * @param Consumer        $consumer
     * @param LoggerInterface $logger
     */
    public function __construct(Exchange $exchange, Queue $queue, Consumer $consumer, LoggerInterface $logger)
    {
        $this->exchange = $exchange;
        $this->queue = $queue;
        $this->consumer = $consumer;
        $this->logger = $logger;
    }

    /**
     * @param callable  $callback
     * @param int|float $timeout
     *
     * @throws QueueException
     * @throws StopReadingException
     */
    public function read(callable $callback, $timeout = 0)
    {
        $exchange = $this->initializeExchange();
        $queue = $this->initializeQueue($exchange);

        try {
            $this->logger->debug("Started reading events from RabbitMQ");
            $this->consumer->start($queue, $callback, $timeout);
        } catch(AMQPTimeoutException $e) {
            $this->logger->debug('Timeout consuming events', ['exception' => $e->getMessage()]);
            throw new StopReadingException("Timed out at $timeout seconds while reading", 0, $e);
        } catch(\Exception $e) {
            $this->logger->error('Error while consuming events', ['exception' => $e->getMessage()]);
            throw new QueueException($e->getMessage(), $e->getCode());
        } finally {
            $this->consumer->stop();
        }
    }

    /**
     * @param DomainEvent[] $events
     *
     * @throws QueueException
     */
    public function write(array $events)
    {
        $this->initializeExchange();

        try {
            $this->exchange->publish($events);
        } catch (Exception $e) {
            $this->logger->error('Error writing to the queue', ['exception' => $e->getMessage()]);
            throw new QueueException($e->getMessage(), $e->getCode());
        }
    }

    /**
     * @return string
     * @throws QueueException
     */
    private function initializeExchange(): string
    {
        try {
            return $this->exchange->create();
        } catch (\Exception $e) {
            $this->logger->error('Error initializing the exchange', ['exception' => $e->getMessage()]);
            throw new QueueException($e->getMessage(), $e->getCode());
        }
    }

    /**
     * @param string $exchange
     *
     * @return string
     * @throws QueueException
     */
    private function initializeQueue(string $exchange): string
    {
        try {
            return $this->queue->createAndBindTo($exchange);
        } catch (\Exception $e) {
            $this->logger->error('Error initializing the queue', ['exception' => $e->getMessage()]);
            throw new QueueException($e->getMessage(), $e->getCode());
        }
    }
}
