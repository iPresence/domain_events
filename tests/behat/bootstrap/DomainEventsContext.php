<?php

namespace behat\IPresence\DomainEvents;

use Behat\Behat\Context\Context;
use behat\IPresence\DomainEvents\Mock\DomainEventSubscriberMock;
use IPresence\DomainEvents\DomainEvent;
use IPresence\DomainEvents\DomainEventFactory;
use IPresence\DomainEvents\Listener\Listener;
use IPresence\DomainEvents\Publisher\Publisher;
use IPresence\DomainEvents\Queue\RabbitMQ\RabbitMQLazyConnection;
use IPresence\DomainEvents\Queue\RabbitMQ\Consumer\RabbitMQConsumer as Consumer;
use IPresence\DomainEvents\Queue\RabbitMQ\Consumer\RabbitMQConsumerConfig;
use IPresence\DomainEvents\Queue\RabbitMQ\Exchange\RabbitMQExchange as Exchange;
use IPresence\DomainEvents\Queue\RabbitMQ\Exchange\RabbitMQExchangeConfig;
use IPresence\DomainEvents\Queue\RabbitMQ\Queue\RabbitMQQueue as Queue;
use IPresence\DomainEvents\Queue\RabbitMQ\Queue\RabbitMQQueueConfig;
use IPresence\DomainEvents\Queue\RabbitMQ\RabbitMQQueue;;
use IPresence\Monitoring\Adapter\NullMonitor;
use PhpAmqpLib\Connection\AMQPLazyConnection;
use Psr\Log\NullLogger;

class DomainEventsContext implements Context
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

    public function __construct()
    {
        $connection = new RabbitMQLazyConnection(new AMQPLazyConnection('rabbit', '5672', 'guest', 'guest'));
        $logger = new NullLogger();
        $monitor = new NullMonitor();
        $factory = new DomainEventFactory();

        $this->exchange = new Exchange($connection, new RabbitMQExchangeConfig('domain-events'));
        $this->queue = new Queue($connection, new RabbitMQQueueConfig('domain-events-test', ['test']));
        $this->consumer = new Consumer($connection, new RabbitMQConsumerConfig());

        $queue = new RabbitMQQueue($this->exchange, $this->queue, $this->consumer, $logger);

        $this->publisher = new Publisher($queue, $monitor, $logger);
        $this->listener = new Listener($queue, $factory, $monitor, $logger);
    }

    /**
     * @Given /^I have a queue ready to handle domain events$/
     */
    public function iHaveAQueueReadyToHandleDomainEvents()
    {
        $exchange = $this->exchange->create();
        $this->queue->createAndBindTo($exchange);
    }

    /**
     * @Given /^I am subscribed to "([^"]*)" events$/
     */
    public function iAmSubscribedToEvents($event)
    {
        $this->subscriber = new DomainEventSubscriberMock($event);
        $this->listener->subscribe($this->subscriber);
    }

    /**
     * @When /^I send a domain event with name "([^"]*)"$/
     */
    public function iSendADomainEventWithName($name)
    {
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
        $this->listener->listen(1);
        if (!$this->subscriber->wasExecuted()) {
            throw new \InvalidArgumentException('The subscriber should be called');
        }
    }

    /**
     * @Then /^I should not consume that event$/
     */
    public function iShouldNotConsumeThatEvent()
    {
        $this->listener->listen(1);
        if ($this->subscriber->wasExecuted()) {
            throw new \InvalidArgumentException('The subscriber should not be called');
        }
    }
}
