<?php

declare(strict_types=1);

namespace Fly\Queue;

trait Dispatchable
{
    /**
     * The name of the queue the job should be sent to.
     */
    protected ?string $queue = null;

    /**
     * The number of seconds before the job should be made available.
     */
    protected int $delay = 0;

    /**
     * Set the name of the queue the job should be sent to.
     */
    public function onQueue(string $queue): self
    {
        $this->queue = $queue;
        return $this;
    }

    /**
     * Set the delay for the job.
     */
    public function delay(int $delay): self
    {
        $this->delay = $delay;
        return $this;
    }

    /**
     * Dispatch the job to the queue.
     */
    public function dispatch(): mixed
    {
        $manager = app('queue');

        if ($this->delay > 0) {
            return $manager->later($this->delay, $this, '', $this->queue);
        }

        return $manager->push($this, '', $this->queue);
    }

    /**
     * Dispatch the job after the response is sent to the browser.
     */
    public static function dispatchAfterResponse(...$arguments): void
    {
        $job = new static(...$arguments);
        
        // This would normally use a Terminator middleware or similar
        // For now, we'll just execute it immediately or use the sync driver
        app('queue')->connection('sync')->push($job);
    }
}
