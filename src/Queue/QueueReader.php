<?php declare(strict_types=1);

namespace IPresence\DomainEvents\Queue;

use IPresence\DomainEvents\Queue\Exception\QueueException;

interface QueueReader
{
    /**
     * @param callable $callback
     *
     * @return int
     *
     * @throws QueueException
     */
    public function read(callable $callback): int;
}
