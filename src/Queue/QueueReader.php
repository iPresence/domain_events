<?php declare(strict_types=1);

namespace IPresence\DomainEvents\Queue;

use IPresence\DomainEvents\Queue\Exception\QueueException;
use IPresence\DomainEvents\Queue\Exception\TimeoutException;

interface QueueReader
{
    /**
     * @param callable $callback
     * @param int      $timeout
     *
     * @throws TimeoutException
     * @throws QueueException
     */
    public function read(callable $callback, $timeout = 0);
}
