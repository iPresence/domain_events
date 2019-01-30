<?php declare(strict_types=1);

namespace IPresence\DomainEvents\Publisher\Fallback;

use IPresence\DomainEvents\DomainEvent;
use IPresence\DomainEvents\DomainEventFactory;
use Ramsey\Uuid\Uuid;
use RuntimeException;

class PublisherFileFallback implements PublisherFallback
{
    const EXTENSION = '.events';

    /**
     * @var DomainEventFactory
     */
    private $factory;

    /**
     * @var string
     */
    private $path;

    /**
     * @param DomainEventFactory $factory
     * @param string             $path
     */
    public function __construct(DomainEventFactory $factory,  string $path = "")
    {
        if (empty($path)) {
            $path = '/tmp/';
        }

        $this->factory = $factory;
        $this->path = $path;
    }

    /**
     * @param DomainEvent[] $events
     *
     * @throws \Exception
     */
    public function store(array $events)
    {
        if (empty($events)) {
            return;
        }

        $data = '';
        foreach ($events as $event) {
            $data .= json_encode($event).PHP_EOL;
            if (json_last_error() != JSON_ERROR_NONE) {
                throw new RuntimeException('Impossible to convert event to JSON', ['reason' => json_last_error_msg()]);
            }
        }

        $file = $this->path.Uuid::uuid4()->toString().self::EXTENSION;
        $success = file_put_contents($file, $data);
        if ($success === false) {
            throw new RuntimeException("Impossible to write the events to the file $file");
        }
    }

    /**
     * @return DomainEvent[]
     *
     * @throws \Exception
     */
    public function restore(): array
    {
        $files = glob($this->path.'*'.self::EXTENSION);
        if (empty($files)) {
            return [];
        }

        // We just want to restore a file each time
        $file = $files[0];

        $events = [];
        $resource = fopen($file, "r+");
        if (flock($resource, LOCK_EX|LOCK_NB)) {
            while (($line = fgets($resource)) !== false) {
                $events[] = $this->factory->fromJSON($line);
            }
            unlink($file);
        }

        return $events;
    }
}
