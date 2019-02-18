<?php declare(strict_types=1);

namespace IPresence\DomainEvents\Queue;

use IPresence\DomainEvents\Queue\Exception\QueueException;
use IPresence\DomainEvents\Queue\Exception\StopReadingException;

interface QueueReader
{
    /**
     * @param callable $callback
     * @param int      $timeout
     *
     * @throws QueueException
     * @throws StopReadingException
     */
    public function read(callable $callback, $timeout = 0);
}
