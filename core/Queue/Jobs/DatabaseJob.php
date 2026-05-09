<?php

declare(strict_types=1);

namespace Fly\Queue\Jobs;

use Fly\Application\Application;
use Fly\Queue\JobInterface;
use Fly\Queue\Drivers\DatabaseQueue;

class DatabaseJob implements JobInterface
{
    /**
     * The application instance.
     *
     * @var Application
     */
    protected Application $app;

    /**
     * The database queue instance.
     *
     * @var DatabaseQueue
     */
    protected DatabaseQueue $queueDriver;

    /**
     * The database job record.
     *
     * @var object
     */
    protected object $job;

    /**
     * The name of the queue the job belongs to.
     *
     * @var string
     */
    protected string $queue;

    /**
     * Create a new database job instance.
     *
     * @param Application $app
     * @param DatabaseQueue $queueDriver
     * @param object $job
     * @param string $queue
     */
    public function __construct(Application $app, DatabaseQueue $queueDriver, object $job, string $queue)
    {
        $this->app = $app;
        $this->queueDriver = $queueDriver;
        $this->job = $job;
        $this->queue = $queue;
    }

    /**
     * Fire the job.
     *
     * @return void
     */
    public function fire(): void
    {
        $payload = json_decode($this->job->payload, true);
        $instance = unserialize($payload['data']);

        $pipeline = new \Fly\Pipeline\Pipeline($this->app);

        $middleware = method_exists($instance, 'middleware') ? $instance->middleware() : [];

        $pipeline->send($instance)
            ->through($middleware)
            ->then(function ($job) {
                if (method_exists($job, 'setJob')) {
                    $job->setJob($this);
                }

                if (method_exists($job, 'handle')) {
                    $this->app->resolveMethod($job, 'handle');
                }
            });
    }

    /**
     * Delete the job from the queue.
     *
     * @return void
     */
    public function delete(): void
    {
        $this->queueDriver->deleteReserved((string) $this->job->id);
    }

    /**
     * Release the job back into the queue.
     *
     * @param int $delay
     * @return void
     */
    public function release(int $delay = 0): void
    {
        $this->queueDriver->release($this->queue, $this->job, $delay);
    }

    /**
     * Get the number of times the job has been attempted.
     *
     * @return int
     */
    public function attempts(): int
    {
        return (int) $this->job->attempts;
    }

    /**
     * Get the job identifier.
     *
     * @return string
     */
    public function getId(): string
    {
        return (string) $this->job->id;
    }

    /**
     * Get the name of the queue the job belongs to.
     *
     * @return string
     */
    public function getQueue(): string
    {
        return $this->queue;
    }

    /**
     * Get the raw body of the job.
     *
     * @return string
     */
    public function getRawBody(): string
    {
        return $this->job->payload;
    }
}
