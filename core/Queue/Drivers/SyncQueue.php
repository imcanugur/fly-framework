<?php

declare(strict_types=1);

namespace Fly\Queue\Drivers;

use Fly\Application\Application;
use Fly\Queue\QueueInterface;
use Fly\Queue\JobInterface;

class SyncQueue implements QueueInterface
{
    /**
     * The application instance.
     *
     * @var Application
     */
    protected Application $app;

    /**
     * Create a new sync queue instance.
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Push a new job onto the queue.
     *
     * @param object $job
     * @param mixed $data
     * @param string|null $queue
     * @return void
     */
    public function push(object $job, $data = '', ?string $queue = null): void
    {
        $this->resolveAndFire($job, $data);
    }

    /**
     * Push a new job onto the queue after a delay.
     *
     * @param int $delay
     * @param object $job
     * @param mixed $data
     * @param string|null $queue
     * @return void
     */
    public function later(int $delay, object $job, $data = '', ?string $queue = null): void
    {
        $this->push($job, $data, $queue);
    }

    /**
     * Pop the next job off of the queue.
     *
     * @param string|null $queue
     * @return JobInterface|null
     */
    public function pop(?string $queue = null): ?JobInterface
    {
        return null;
    }

    /**
     * Get the size of the queue.
     *
     * @param string|null $queue
     * @return int
     */
    public function size(?string $queue = null): int
    {
        return 0;
    }

    /**
     * Resolve and fire the job.
     *
     * @param object|string $job
     * @param mixed $data
     * @return void
     */
    protected function resolveAndFire($job, $data): void
    {
        if (method_exists($job, 'handle')) {
            $this->app->resolveMethod($job, 'handle', ['data' => $data]);
        }
    }
}
