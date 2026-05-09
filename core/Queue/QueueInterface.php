<?php

declare(strict_types=1);

namespace Fly\Queue;

interface QueueInterface
{
    /**
     * Push a new job onto the queue.
     *
     * @param object $job
     * @param mixed $data
     * @param string|null $queue
     * @return mixed
     */
    public function push(object $job, $data = '', ?string $queue = null);

    /**
     * Push a new job onto the queue after a delay.
     *
     * @param int $delay
     * @param object $job
     * @param mixed $data
     * @param string|null $queue
     * @return mixed
     */
    public function later(int $delay, object $job, $data = '', ?string $queue = null);

    /**
     * Pop the next job off of the queue.
     *
     * @param string|null $queue
     * @return JobInterface|null
     */
    public function pop(?string $queue = null): ?JobInterface;

    /**
     * Get the size of the queue.
     *
     * @param string|null $queue
     * @return int
     */
    public function size(?string $queue = null): int;
}
