<?php declare(strict_types=1);

namespace IPresence\DomainEvents\Queue\RabbitMQ\Queue;

class RabbitMQQueueConfig
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var array
     */
    private $bindings;

    /**
     * @var bool
     */
    protected $passive;

    /**
     * @var bool
     */
    protected $durable;

    /**
     * @var bool
     */
    private $exclusive;

    /**
     * @var bool
     */
    protected $autoDelete;

    /**
     * @param string $name
     * @param array  $bindings
     * @param bool   $passive
     * @param bool   $durable
     * @param bool   $exclusive
     * @param bool   $autoDelete
     */
    public function __construct(
        string $name,
        array $bindings = [],
        bool $passive = false,
        bool $durable = true,
        bool $exclusive = false,
        bool $autoDelete = false
    ) {
        $this->name = $name;
        $this->bindings = $bindings;
        $this->passive = $passive;
        $this->durable = $durable;
        $this->exclusive = $exclusive;
        $this->autoDelete = $autoDelete;
    }

    /**
     * @return string
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * @return array
     */
    public function bindings(): array
    {
        return $this->bindings;
    }

    /**
     * @return bool
     */
    public function passive(): bool
    {
        return $this->passive;
    }

    /**
     * @return bool
     */
    public function durable(): bool
    {
        return $this->durable;
    }

    /**
     * @return bool
     */
    public function exclusive(): bool
    {
        return $this->exclusive;
    }

    /**
     * @return bool
     */
    public function autoDelete(): bool
    {
        return $this->autoDelete;
    }
}
