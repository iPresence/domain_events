<?php

namespace behat\IPresence\DomainEvents;

use Behat\Behat\Context\Context;
use behat\IPresence\DomainEvents\Mock\DomainEventSubscriberMock;
use Google\Cloud\PubSub\PubSubClient;
use IPresence\DomainEvents\DomainEvent;
use IPresence\DomainEvents\DomainEventFactory;
use IPresence\DomainEvents\Listener\Listener;
use IPresence\DomainEvents\Listener\ListenerBuilder;
use IPresence\DomainEvents\Publisher\Fallback\PublisherFileFallback;
use IPresence\DomainEvents\Publisher\Publisher;
use IPresence\DomainEvents\Queue\Google\GoogleCloudQueue;
use IPresence\DomainEvents\Queue\RabbitMQ\RabbitMQLazyConnection;
use IPresence\DomainEvents\Queue\RabbitMQ\Consumer\RabbitMQConsumer as Consumer;
use IPresence\DomainEvents\Queue\RabbitMQ\Consumer\RabbitMQConsumerConfig;
use IPresence\DomainEvents\Queue\RabbitMQ\Exchange\RabbitMQExchange as Exchange;
use IPresence\DomainEvents\Queue\RabbitMQ\Exchange\RabbitMQExchangeConfig;
use IPresence\DomainEvents\Queue\RabbitMQ\Queue\RabbitMQQueue as Queue;
use IPresence\DomainEvents\Queue\RabbitMQ\Queue\RabbitMQQueueConfig;
use IPresence\DomainEvents\Queue\RabbitMQ\RabbitMQQueue;
use IPresence\DomainEvents\Symfony\DomainEventsReceiver;
use IPresence\DomainEvents\Symfony\DomainEventsSender;
use IPresence\Monitoring\Adapter\NullMonitor;
use IPresence\Monitoring\Monitor;
use PhpAmqpLib\Connection\AMQPLazyConnection;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Messenger\Envelope;

class DomainEventsContext implements Context
{
    /**
     * @var array
     */
    private $queues = [];

    /**
     * @var Publisher
     */
    private $publisher;

    /**
     * @var Listener
     */
    private $listener;

    /**
     * @var DomainEventSubscriberMock
     */
    private $subscriber;

    /**
     * @var PublisherFileFallback
     */
    private $fallback;

    /**
     * @var DomainEventFactory
     */
    private $factory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Monitor
     */
    private $monitor;

    public function __construct()
    {
        $this->logger = new NullLogger();
        $this->monitor = new NullMonitor();
        $this->factory = new DomainEventFactory();
    }

    /**
     * @Given /^I have a google queue ready to handle domain events$/
     */
    public function iHaveAGoogleQueueReadyToHandleDomainEvents()
    {
        $this->queues[] = new GoogleCloudQueue(
            new PubSubClient(),
            'domain-events-test',
            'test',
            $this->logger
        );
    }

    /**
     * @Given /^I have a rabbit queue ready to handle domain events$/
     */
    public function iHaveARabbitQueueReadyToHandleDomainEvents()
    {
        $connection = new RabbitMQLazyConnection(new AMQPLazyConnection('rabbit', '5672', 'guest', 'guest'));

        $exchange = new Exchange($connection, new RabbitMQExchangeConfig('domain-events'));
        $queue = new Queue($connection, new RabbitMQQueueConfig('domain-events-test', ['test']));
        $consumer = new Consumer($connection, new RabbitMQConsumerConfig());

        $this->queues[] = new RabbitMQQueue($exchange, $queue, $consumer, $this->logger);
    }

    /**
     * @Given /^The writers are not working$/
     */
    public function theWritersAreNotWorking()
    {
        $this->queues = [];
        $this->fallback = new PublisherFileFallback($this->factory, './');
        $this->publisher = new Publisher($this->queues, $this->fallback, $this->monitor, $this->logger);
    }

    /**
     * @Given /^I am subscribed to "([^"]*)" events$/
     */
    public function iAmSubscribedToEvents($event)
    {
        $this->subscriber = new DomainEventSubscriberMock($event);

        $builder = (new ListenerBuilder())
            ->withLogger($this->logger)
            ->withMonitor($this->monitor)
            ->withIdleTime(100);

        foreach ($this->queues as $reader) {
            $builder->addReader($reader);
        }

        $this->listener = $builder->build();
        $this->listener->subscribe($this->subscriber);
    }

    /**
     * @When /^I send a domain event with name "([^"]*)"$/
     */
    public function iSendADomainEventWithName($name)
    {
        $this->fallback = new PublisherFileFallback($this->factory, './');
        $this->publisher = new Publisher($this->queues, $this->fallback, $this->monitor, $this->logger);

        $event = new DomainEvent(
            'test',
            $name,
            'v1.0.0',
            new \DateTimeImmutable()
        );

        $this->publisher->add($event);
        $this->publisher->publish();
    }

    /**
     * @Then /^I should consume that event$/
     */
    public function iShouldConsumeThatEvent()
    {
        $this->listener->listen();
        if (!$this->subscriber->wasExecuted()) {
            throw new \InvalidArgumentException('The subscriber should be called');
        }
    }

    /**
     * @Then /^I should not consume that event$/
     */
    public function iShouldNotConsumeThatEvent()
    {
        $this->listener->listen();
        if ($this->subscriber->wasExecuted()) {
            throw new \InvalidArgumentException('The subscriber should not be called');
        }
    }

    /**
     * @When /^I send a domain event with name "([^"]*)" through the Symfony sender$/
     */
    public function iSendADomainEventWithNameThroughTheSymfonySender($name)
    {
        $this->fallback = new PublisherFileFallback($this->factory, './');
        $this->publisher = new Publisher($this->queues, $this->fallback, $this->monitor, $this->logger);

        $sender = new DomainEventsSender($this->publisher);

        $event = new DomainEvent(
            'test',
            $name,
            'v1.0.0',
            new \DateTimeImmutable()
        );

        $sender->send(new Envelope($event));
    }

    /**
     * @Then /^I should consume that event from the Symfony receiver$/
     */
    public function iShouldConsumeThatEventFromTheSymfonyReceiver()
    {
        $receiver = new DomainEventsReceiver($this->listener);
        $receiver->receive(function() use($receiver) {
            $receiver->stop();
        });

        if (!$this->subscriber->wasExecuted()) {
            throw new \InvalidArgumentException('The subscriber should be called');
        }
    }

    /**
     * @Then /^I have this event stored$/
     */
    public function iHaveThisEventStored()
    {
        $events = $this->fallback->restore();
        if (count($events) != 1) {
            throw new \InvalidArgumentException('The event should be stored in a file');
        }
    }
}
