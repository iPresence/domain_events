<?php

namespace IPresence\DomainEvents\Queue;

use IPresence\DomainEvents\Queue\Exception\GracefulStopException;
use IPresence\DomainEvents\Queue\Exception\ReaderException;
use IPresence\DomainEvents\Queue\Exception\TimeoutException;

interface QueueReader
{
    /**
     * @param callable $callback
     * @param int      $timeout
     *
     * @throws TimeoutException
     * @throws GracefulStopException
     * @throws ReaderException
     */
    public function read(callable $callback, $timeout = 0);
}
