<?php

namespace IPresence\DomainEvents\Publisher;

use InvalidArgumentException;
use IPresence\DomainEvents\Queue\QueueWriter;
use IPresence\DomainEvents\Queue\RabbitMQ\RabbitMQBuilder;
use IPresence\Monitoring\Adapter\NullMonitor;
use IPresence\Monitoring\Monitor;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RuntimeException;
use Symfony\Component\Yaml\Yaml;

class PublisherBuilder
{
    /**
     * @var QueueWriter
     */
    private $writer;

    /**
     * @var Monitor
     */
    private $monitor;

    /**
     * @var LoggerInterface|null
     */
    private $logger;

    /**
     * Returns a new instance to start start building the publisher
     *
     * @return PublisherBuilder
     */
    public static function create(): self
    {
        return new self();
    }

    /**
     * Setups the writer to use to publish the events
     *
     * @param QueueWriter $writer
     *
     * @return $this
     */
    public function withWriter(QueueWriter $writer)
    {
        $this->writer = $writer;

        return $this;
    }

    /**
     * Setups the requests configuration
     *
     * @param array $config
     *
     * @return $this
     */
    public function withConfig(array $config)
    {
        if (isset($config['rabbit'])) {
            $this->writer = RabbitMQBuilder::create()->withConfig($config['rabbit'])->build();
        } else {
            throw new InvalidArgumentException('The configuration is invalid, rabbit values expected');
        }

        return $this;
    }

    /**
     * Allows you load the configuration from a yaml file
     *
     * IMPORTANT: you need to install 'symfony/yaml' to use this feature
     *
     * @param string $file
     *
     * @return $this
     */
    public function withYamlConfig($file)
    {
        if (!class_exists(Yaml::class)) {
            throw new RuntimeException("You need to install 'symfony/yaml' to use this feature");
        }

        if (!is_readable($file)) {
            throw new RuntimeException("The configuration file is not readable");
        }

        $config = Yaml::parse(file_get_contents($file));
        if (!is_array($config)) {
            throw new RuntimeException("The configuration is not valid");
        }

        $this->withConfig($config);

        return $this;
    }

    /**
     * Sets a logger to use
     *
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
     * Sets a monitor to use
     *
     * @param Monitor $monitor
     *
     * @return $this
     */
    public function withMonitor(Monitor $monitor)
    {
        $this->monitor = $monitor;

        return $this;
    }

    /**
     * Builds the publisher with the configured values
     *
     * @return Publisher
     */
    public function build(): Publisher
    {
        if (!$this->writer) {
            throw new RuntimeException("You need to provide a configuration or a queue writer");
        }

        if (!$this->logger) {
            $this->logger = new NullLogger();
        }

        if (!$this->monitor) {
            $this->monitor = new NullMonitor();
        }

        return new Publisher($this->writer, $this->monitor, $this->logger);
    }

    /**
     * @param array                $config
     * @param Monitor|null         $monitor
     * @param LoggerInterface|null $logger
     *
     * @return Publisher
     */
    public static function buildFromConfig(array $config, Monitor $monitor = null, LoggerInterface $logger = null): Publisher
    {
        return self::create()
            ->withConfig($config)
            ->withMonitor($monitor ?? new NullMonitor())
            ->withLogger($logger ?? new NullLogger())
            ->build();
    }
}
