<?php declare(strict_types=1);

namespace IPresence\DomainEvents\Queue\RabbitMQ\Exchange;

class RabbitMQExchangeConfig
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $type;

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
    protected $autoDelete;

    /**
     * @param string $name
     * @param string $type
     * @param bool   $passive
     * @param bool   $durable
     * @param bool   $autoDelete
     */
    public function __construct(string $name, string $type = 'direct', bool $passive = false, bool $durable = true, bool $autoDelete = false)
    {
        $this->name = $name;
        $this->type = $type;
        $this->passive = $passive;
        $this->durable = $durable;
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
     * @return string
     */
    public function type(): string
    {
        return $this->type;
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
    public function autoDelete(): bool
    {
        return $this->autoDelete;
    }
}
