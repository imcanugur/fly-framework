<?php

declare(strict_types=1);

namespace Fly\Queue;

class PendingChain
{
    /**
     * The list of jobs in the chain.
     */
    protected array $jobs;

    /**
     * The queue the chain should run on.
     */
    protected ?string $queue = null;

    /**
     * Create a new pending chain instance.
     */
    public function __construct(array $jobs)
    {
        $this->jobs = $jobs;
    }

    /**
     * Set the queue for the chain.
     */
    public function onQueue(string $queue): self
    {
        $this->queue = $queue;
        return $this;
    }

    /**
     * Dispatch the chain.
     */
    public function dispatch(): void
    {
        if (empty($this->jobs)) {
            return;
        }

        $firstJob = array_shift($this->jobs);
        
        // We attach the rest of the chain to the first job
        if (!empty($this->jobs) && method_exists($firstJob, 'withChain')) {
            $firstJob->withChain($this->jobs);
        }

        app('queue')->push($firstJob, '', $this->queue);
    }
}
