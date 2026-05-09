<?php

declare(strict_types=1);

namespace Fly\Queue;

trait InteractsWithQueue
{
    /**
     * The job instance (if currently being processed).
     */
    protected ?JobInterface $job = null;

    /**
     * Set the job instance.
     */
    public function setJob(JobInterface $job): self
    {
        $this->job = $job;
        return $this;
    }

    /**
     * Delete the job from the queue.
     */
    public function delete(): void
    {
        if ($this->job) {
            $this->job->delete();
        }
    }

    /**
     * Release the job back into the queue.
     */
    public function release(int $delay = 0): void
    {
        if ($this->job) {
            $this->job->release($delay);
        }
    }

    /**
     * Get the number of times the job has been attempted.
     */
    public function attempts(): int
    {
        return $this->job ? $this->job->attempts() : 0;
    }

    /**
     * The remaining jobs in the chain.
     */
    public array $chained = [];

    /**
     * Set the remaining jobs in the chain.
     */
    public function withChain(array $chain): self
    {
        $this->chained = $chain;
        return $this;
    }

    /**
     * Dispatch the next job in the chain.
     */
    public function dispatchNextJobInChain(): void
    {
        if (!empty($this->chained)) {
            $nextJob = array_shift($this->chained);
            
            if (!empty($this->chained) && method_exists($nextJob, 'withChain')) {
                $nextJob->withChain($this->chained);
            }

            app('queue')->push($nextJob);
        }
    }
}
