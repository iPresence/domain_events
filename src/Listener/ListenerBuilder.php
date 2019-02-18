<?php declare(strict_types=1);

namespace IPresence\DomainEvents\Listener;

use InvalidArgumentException;
use IPresence\DomainEvents\DomainEventFactory;
use IPresence\DomainEvents\Queue\Google\GoogleCloudBuilder;
use IPresence\DomainEvents\Queue\Google\GoogleCloudQueue;
use IPresence\DomainEvents\Queue\QueueReader;
use IPresence\DomainEvents\Queue\RabbitMQ\RabbitMQBuilder;
use IPresence\Monitoring\Adapter\NullMonitor;
use IPresence\Monitoring\Monitor;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RuntimeException;
use Symfony\Component\Yaml\Yaml;

class ListenerBuilder
{
    /**
     * @var QueueReader[]
     */
    private $readers;

    /**
     * @var DomainEventFactory
     */
    private $factory;

    /**
     * @var Monitor
     */
    private $monitor;

    /**
     * @var LoggerInterface|null
     */
    private $logger;

    /**
     * Returns a new instance to start start building the listener
     *
     * @return ListenerBuilder
     */
    public static function create(): self
    {
        return new self();
    }

    /**
     * @param QueueReader $reader
     *
     * @return $this
     */
    public function addReader(QueueReader $reader)
    {
        $this->readers[] = $reader;

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
        if (isset($config['provider']['rabbit'])) {
            $this->readers[] = RabbitMQBuilder::create()->withConfig($config['provider']['rabbit'])->build();
        }
        if (isset($config['provider']['google'])) {
            $this->readers[] = GoogleCloudBuilder::create()->withConfig($config['provider']['google'])->build();
        }

        if (isset($config['mapping'])) {
            $this->factory = new DomainEventFactory($config['mapping']);
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
     * Builds the listener with the configured values
     *
     * @return Listener
     */
    public function build(): Listener
    {
        if (empty($this->readers)) {
            throw new RuntimeException("You need to provide a configuration or a queue reader");
        }

        if (!$this->factory) {
            $this->factory = new DomainEventFactory();
        }

        if (!$this->monitor) {
            $this->monitor = new NullMonitor();
        }

        if (!$this->logger) {
            $this->logger = new NullLogger();
        }

        return new Listener($this->readers, $this->factory, $this->monitor, $this->logger);
    }

    /**
     * @param array                $config
     * @param Monitor|null         $monitor
     * @param LoggerInterface|null $logger
     *
     * @return Listener
     */
    public static function buildFromConfig(array $config, Monitor $monitor = null, LoggerInterface $logger = null): Listener
    {
        return self::create()
            ->withConfig($config)
            ->withMonitor($monitor ?? new NullMonitor())
            ->withLogger($logger ?? new NullLogger())
            ->build();
    }
}
