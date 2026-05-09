<?php

declare(strict_types=1);

namespace Fly\Queue;

class Bus
{
    /**
     * Dispatch a job to its appropriate handler.
     */
    public static function dispatch($job): mixed
    {
        return app('queue')->push($job);
    }

    /**
     * Create a new chain of jobs to be executed sequentially.
     * 
     * @param array $jobs
     * @return PendingChain
     */
    public static function chain(array $jobs): PendingChain
    {
        return new PendingChain($jobs);
    }

    /**
     * Create a new batch of jobs to be executed.
     * 
     * @param array $jobs
     * @return PendingBatch
     */
    public static function batch(array $jobs): PendingBatch
    {
        return new PendingBatch($jobs);
    }
}
