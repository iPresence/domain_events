<?php declare(strict_types=1);

namespace IPresence\DomainEvents\Queue\Google;

use Google\Cloud\PubSub\PubSubClient;
use Google\Cloud\PubSub\Subscription;
use Google\Cloud\PubSub\Topic;
use IPresence\DomainEvents\DomainEvent;
use IPresence\DomainEvents\Queue\Exception\QueueException;
use IPresence\DomainEvents\Queue\Exception\StopReadingException;
use IPresence\DomainEvents\Queue\QueueReader;
use IPresence\DomainEvents\Queue\QueueWriter;
use Psr\Log\LoggerInterface;

class GoogleCloudQueue implements QueueReader, QueueWriter
{
    /**
     * @var PubSubClient
     */
    private $client;

    /**
     * @var string
     */
    private $topicName;

    /**
     * @var string
     */
    private $subscriptionName;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param PubSubClient    $client
     * @param string          $topicName
     * @param string          $subscriptionName
     * @param LoggerInterface $logger
     */
    public function __construct(PubSubClient $client, string $topicName, string $subscriptionName, LoggerInterface $logger)
    {
        $this->client = $client;
        $this->topicName = $topicName;
        $this->subscriptionName = $subscriptionName;
        $this->logger = $logger;
    }

    /**
     * @param callable $callback
     * @param int      $timeout
     *
     * @throws QueueException
     * @throws StopReadingException
     */
    public function read(callable $callback, $timeout = 0)
    {
        $this->logger->debug("Started reading events from Google Pub/Sub");

        $topic = $this->getTopic($this->topicName);
        $subscription = $this->getSubscription($this->subscriptionName, $topic->name());

        while (true) {
            $messages = $subscription->pull(['returnImmediately' => true]);
            if (empty($messages)) {
                throw new StopReadingException();
            }

            foreach ($messages as $message) {
                call_user_func($callback, $message->data());
                $subscription->acknowledge($message);
            }
        }
    }

    /**
     * @param DomainEvent[] $events
     *
     * @throws QueueException
     */
    public function write(array $events)
    {
        $topic = $this->getTopic($this->topicName);

        $formattedEvents = [];
        foreach($events as $event) {
            $formattedEvents[] = ['data' => json_encode($event)];
        }
        $topic->publishBatch($formattedEvents);
    }

    /**
     * @param string $name
     *
     * @return Topic
     * @throws QueueException
     */
    public function getTopic(string $name): Topic
    {
        $topic = $this->client->topic($name);
        if ($topic->exists()) {
            return $topic;
        }

        try {
            $topic->create();
            return $topic;
        } catch (\Exception $e) {
            $this->logger->error('Error initializing the topic', ['exception' => $e->getMessage()]);
            throw new QueueException($e->getMessage(), $e->getCode());
        }
    }

    /**
     * @param string $name
     * @param string $topic
     *
     * @return Subscription
     * @throws QueueException
     */
    public function getSubscription(string $name, string $topic): Subscription
    {
        $subscription = $this->client->subscription($name, $topic);
        if ($subscription->exists()) {
            return $subscription;
        }

        try {
            $subscription->create();
            return $subscription;
        } catch (\Exception $e) {
            $this->logger->error('Error initializing the topic', ['exception' => $e->getMessage()]);
            throw new QueueException($e->getMessage(), $e->getCode());
        }
    }
}
