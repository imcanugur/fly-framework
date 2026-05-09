<?php

declare(strict_types=1);

namespace Fly\Queue;

use Fly\Application\Application;
use Fly\Queue\Events\JobFailed;
use Fly\Queue\Events\JobProcessed;
use Fly\Queue\Events\JobProcessing;
use Throwable;

class Worker
{
    /**
     * The application instance.
     */
    protected Application $app;

    /**
     * The queue manager instance.
     */
    protected QueueManager $manager;

    /**
     * Indicates if the worker should stop.
     */
    protected bool $shouldQuit = false;

    /**
     * Indicates if the worker is paused.
     */
    protected bool $paused = false;

    /**
     * Create a new worker instance.
     */
    public function __construct(Application $app, QueueManager $manager)
    {
        $this->app = $app;
        $this->manager = $manager;
    }

    /**
     * Run the worker.
     */
    public function run(?string $connection = null, ?string $queue = null, array $options = []): void
    {
        $this->listenForSignals();

        $sleep = $options['sleep'] ?? 3;
        $memoryLimit = $options['memory'] ?? 128;

        $this->app->logger->info("Fly Queue Worker started for [{$connection}] on queue [{$queue}]");

        while (true) {
            if ($this->shouldQuit) {
                $this->stop();
            }

            if ($this->paused) {
                sleep($sleep);
                continue;
            }

            $job = $this->getNextJob($connection, $queue);

            if ($job) {
                $this->process($job);
            } else {
                sleep($sleep);
            }

            if ($this->memoryExceeded($memoryLimit)) {
                $this->stop("Memory limit exceeded: {$memoryLimit}MB");
            }
        }
    }

    /**
     * Process a given job.
     */
    public function process(JobInterface $job): void
    {
        try {
            $this->raiseEvent(new JobProcessing($job));

            $job->fire();

            $job->delete();

            $this->raiseEvent(new JobProcessed($job));
        } catch (Throwable $e) {
            $this->handleJobFailure($job, $e);
        }
    }

    /**
     * Handle a job failure.
     */
    protected function handleJobFailure(JobInterface $job, Throwable $e): void
    {
        $this->raiseEvent(new JobFailed($job, $e));

        if ($job->attempts() < 3) {
            $delay = pow(2, $job->attempts()) * 5; // Exponential backoff
            $job->release($delay);
            $this->app->logger->warning("Job #{$job->getId()} failed, retrying in {$delay}s. Error: {$e->getMessage()}");
        } else {
            $this->markAsFailed($job, $e);
            $this->app->logger->error("Job #{$job->getId()} failed permanently. Error: {$e->getMessage()}");
        }
    }

    /**
     * Get the next job from the queue.
     */
    protected function getNextJob(?string $connection, ?string $queue): ?JobInterface
    {
        try {
            return $this->manager->connection($connection)->pop($queue);
        } catch (Throwable $e) {
            $this->app->logger->critical("Queue Connection Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Mark a job as failed.
     */
    protected function markAsFailed(JobInterface $job, Throwable $e): void
    {
        $this->app->db->table('failed_jobs')->insert([
            'uuid' => json_decode($job->getRawBody(), true)['uuid'] ?? '',
            'connection' => $this->manager->getDefaultDriver(),
            'queue' => $job->getQueue(),
            'payload' => $job->getRawBody(),
            'exception' => (string) $e,
            'failed_at' => time(),
        ]);

        $job->delete();
    }

    /**
     * Listen for termination signals.
     */
    protected function listenForSignals(): void
    {
        if (extension_loaded('pcntl')) {
            pcntl_async_signals(true);

            pcntl_signal(SIGTERM, fn() => $this->shouldQuit = true);
            pcntl_signal(SIGINT, fn() => $this->shouldQuit = true);
            pcntl_signal(SIGUSR2, fn() => $this->paused = true);
            pcntl_signal(SIGCONT, fn() => $this->paused = false);
        }
    }

    /**
     * Determine if the memory limit has been exceeded.
     */
    protected function memoryExceeded(int $memoryLimit): bool
    {
        return (memory_get_usage(true) / 1024 / 1024) >= $memoryLimit;
    }

    /**
     * Stop the worker.
     */
    public function stop(string $reason = 'Manual termination'): void
    {
        $this->app->logger->info("Worker stopping... Reason: {$reason}");
        exit(0);
    }

    /**
     * Raise a framework event.
     */
    protected function raiseEvent(object $event): void
    {
        if ($this->app->has('events')) {
            $this->app->make('events')->dispatch($event);
        }
    }
}
