<?php declare(strict_types=1);

namespace IPresence\DomainEvents;

use DateTimeImmutable;
use Exception;
use InvalidArgumentException;
use JsonSerializable;
use Ramsey\Uuid\Uuid;

class DomainEvent implements JsonDeserializable, JsonSerializable
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    protected $origin;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $version;

    /**
     * @var DateTimeImmutable
     */
    protected $occurredOn;

    /**
     * @var array
     */
    protected $body = [];

    /**
     * @var bool
     */
    protected $deprecated = false;

    /**
     * @var string|null
     */
    protected $correlationId = null;

    /**
     * @param string            $origin
     * @param string            $name
     * @param string            $version
     * @param DateTimeImmutable $occurredOn
     * @param array             $body
     * @param string            $id
     * @param bool              $deprecated
     * @param string|null       $correlationId
     *
     * @throws Exception
     */
    public function __construct(
        string $origin,
        string $name,
        string $version,
        DateTimeImmutable $occurredOn,
        array $body = [],
        string $id = null,
        bool $deprecated = false,
        string $correlationId = null
    ) {
        if (empty($name)) {
            throw new InvalidArgumentException('Domain Event name can not be empty');
        }

        $this->id            = $id ?? Uuid::uuid4()->toString();
        $this->name          = $name;
        $this->origin        = $origin;
        $this->version       = $version;
        $this->occurredOn    = $occurredOn;
        $this->body          = $body;
        $this->deprecated    = $deprecated;
        $this->correlationId = $correlationId;
    }

    /**
     * @return string
     */
    public function id(): string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function origin(): string
    {
        return $this->origin;
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
    public function version(): string
    {
        return $this->version;
    }

    /**
     * @return DateTimeImmutable
     */
    public function occurredOn(): DateTimeImmutable
    {
        return $this->occurredOn;
    }

    /**
     * @return array
     */
    public function body(): array
    {
        return $this->body;
    }

    /**
     * @return bool
     */
    public function isDeprecated(): bool
    {
        return $this->deprecated;
    }

    /**
     * @return string|null
     */
    public function correlationId(): ?string
    {
        return $this->correlationId;
    }

    /**
     * @return array
     */
    public function jsonSerialize(): array
    {
        return [
            'id'            => $this->id,
            'name'          => $this->name,
            'origin'        => $this->origin,
            'version'       => $this->version,
            'occurredOn'    => $this->occurredOn->getTimestamp(),
            'body'          => $this->body,
            'deprecated'    => $this->deprecated,
            'correlationId' => $this->correlationId,
        ];
    }

    /**
     * @param array $data
     *
     * @return DomainEvent
     * @throws Exception
     */
    public static function jsonDeserialize(array $data)
    {
        return new self(
            $data['origin'],
            $data['name'],
            $data['version'],
            (new DateTimeImmutable())->setTimestamp($data['occurredOn']),
            $data['body'],
            $data['id'],
            $data['deprecated'] ?? false,
            $data['correlationId'] ?? null
        );
    }
}
