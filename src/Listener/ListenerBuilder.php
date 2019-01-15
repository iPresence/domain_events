<?php

namespace IPresence\DomainEvents\Listener;

use InvalidArgumentException;
use IPresence\DomainEvents\Queue\QueueReader;
use IPresence\DomainEvents\Queue\RabbitMQ\RabbitMQBuilder;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RuntimeException;
use Symfony\Component\Yaml\Yaml;

class ListenerBuilder
{
    /**
     * @var QueueReader
     */
    private $reader;

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
     * Setups the requests configuration
     *
     * @param array $config
     *
     * @return $this
     */
    public function withConfig(array $config)
    {
        if (isset($config['rabbit'])) {
            $this->reader = RabbitMQBuilder::create()->withConfig($config['rabbit'])->buildReader();
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
     * Builds the listener with the configured values
     *
     * @return Listener
     */
    public function build(): Listener
    {
        if (!$this->reader) {
            throw new RuntimeException("You need to provide a configuration or a queue reader");
        }

        if (!$this->logger) {
            $this->logger = new NullLogger();
        }

        return new Listener($this->reader, $this->logger);
    }
}