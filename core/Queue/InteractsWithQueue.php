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
}
