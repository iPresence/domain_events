<?php declare(strict_types=1);

namespace IPresence\DomainEvents\Publisher;

use IPresence\DomainEvents\DomainEventFactory;
use IPresence\DomainEvents\Publisher\Fallback\PublisherFallback;
use IPresence\DomainEvents\Publisher\Fallback\PublisherFileFallback;
use IPresence\DomainEvents\Queue\Google\GoogleCloudBuilder;
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
     * @var QueueWriter[]
     */
    private $writers;

    /**
     * @var PublisherFallback
     */
    private $fallback;

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
     * Adds a new writer to use to publish the events
     *
     * @param QueueWriter $writer
     *
     * @return $this
     */
    public function addWriter(QueueWriter $writer)
    {
        $this->writers[] = $writer;

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
            $this->writers[] = RabbitMQBuilder::create()->withConfig($config['provider']['rabbit'])->build();
        }

        if (isset($config['provider']['google'])) {
            $this->writers[] = GoogleCloudBuilder::create()->withConfig($config['provider']['google'])->build();
        }

        $factory = new DomainEventFactory($config['mapping'] ?? []);
        $this->fallback = new PublisherFileFallback($factory);

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
        if (empty($this->writers)) {
            throw new RuntimeException("You need to provide a configuration or a queue writer");
        }

        if (!$this->fallback) {
            $this->fallback = new PublisherFileFallback(new DomainEventFactory());
        }

        if (!$this->logger) {
            $this->logger = new NullLogger();
        }

        if (!$this->monitor) {
            $this->monitor = new NullMonitor();
        }

        return new Publisher($this->writers, $this->fallback, $this->monitor, $this->logger);
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
