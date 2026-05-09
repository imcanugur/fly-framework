<?php

declare(strict_types=1);

namespace Fly\Queue;

use Fly\Application\Application;
use Throwable;

class Worker
{
    /**
     * The application instance.
     *
     * @var Application
     */
    protected Application $app;

    /**
     * The queue manager instance.
     *
     * @var QueueManager
     */
    protected QueueManager $manager;

    /**
     * Create a new worker instance.
     *
     * @param Application $app
     * @param QueueManager $manager
     */
    public function __construct(Application $app, QueueManager $manager)
    {
        $this->app = $app;
        $this->manager = $manager;
    }

    /**
     * Run the worker.
     *
     * @param string|null $connection
     * @param string|null $queue
     * @param int $sleep
     * @return void
     */
    public function run(?string $connection = null, ?string $queue = null, int $sleep = 3): void
    {
        while (true) {
            $job = $this->getNextJob($connection, $queue);

            if ($job) {
                $this->process($job);
            } else {
                sleep($sleep);
            }
        }
    }

    /**
     * Get the next job from the queue.
     *
     * @param string|null $connection
     * @param string|null $queue
     * @return JobInterface|null
     */
    protected function getNextJob(?string $connection, ?string $queue): ?JobInterface
    {
        try {
            return $this->manager->connection($connection)->pop($queue);
        } catch (Throwable $e) {
            $this->app->logger->error("Queue Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Process a given job.
     *
     * @param JobInterface $job
     * @return void
     */
    public function process(JobInterface $job): void
    {
        try {
            $job->fire();
            $job->delete();
        } catch (Throwable $e) {
            $this->app->logger->error("Job Failed: " . $e->getMessage());
            
            if ($job->attempts() < 3) {
                $job->release(10);
            } else {
                $this->markAsFailed($job, $e);
            }
        }
    }

    /**
     * Mark a job as failed.
     *
     * @param JobInterface $job
     * @param Throwable $e
     * @return void
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
}
