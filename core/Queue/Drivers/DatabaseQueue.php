<?php

declare(strict_types=1);

namespace Fly\Queue\Drivers;

use Fly\Database\Connection;
use Fly\Queue\QueueInterface;
use Fly\Queue\JobInterface;
use Fly\Queue\Jobs\DatabaseJob;
use Fly\Support\Str;

class DatabaseQueue implements QueueInterface
{
    /**
     * The database connection instance.
     *
     * @var Connection
     */
    protected Connection $database;

    /**
     * The database table that holds the jobs.
     *
     * @var string
     */
    protected string $table;

    /**
     * The name of the default queue.
     *
     * @var string
     */
    protected string $default;

    /**
     * The expiration time of a job in seconds.
     *
     * @var int
     */
    protected int $retryAfter;

    /**
     * Create a new database queue instance.
     *
     * @param Connection $database
     * @param string $table
     * @param string $default
     * @param int $retryAfter
     */
    public function __construct(Connection $database, string $table, string $default = 'default', int $retryAfter = 60)
    {
        $this->database = $database;
        $this->table = $table;
        $this->default = $default;
        $this->retryAfter = $retryAfter;
    }

    /**
     * Push a new job onto the queue.
     *
     * @param object $job
     * @param mixed $data
     * @param string|null $queue
     * @return mixed
     */
    public function push(object $job, $data = '', ?string $queue = null)
    {
        return $this->pushToDatabase($queue, $this->createPayload($job, $data));
    }

    /**
     * Push a new job onto the queue after a delay.
     *
     * @param int $delay
     * @param object $job
     * @param mixed $data
     * @param string|null $queue
     * @return mixed
     */
    public function later(int $delay, object $job, $data = '', ?string $queue = null)
    {
        return $this->pushToDatabase($queue, $this->createPayload($job, $data), $delay);
    }

    /**
     * Pop the next job off of the queue.
     *
     * @param string|null $queue
     * @return JobInterface|null
     */
    public function pop(?string $queue = null): ?JobInterface
    {
        $queue = $queue ?: $this->default;

        return $this->database->transaction(function () use ($queue) {
            if ($job = $this->getNextAvailableJob($queue)) {
                return $this->marshalJob($queue, $job);
            }

            return null;
        });
    }

    /**
     * Get the next available job for the queue.
     *
     * @param string $queue
     * @return object|null
     */
    protected function getNextAvailableJob(string $queue): ?object
    {
        $job = $this->database->table($this->table)
            ->where('queue', $queue)
            ->where(function ($query) {
                $query->whereNull('reserved_at')
                    ->orWhere('reserved_at', '<=', time() - $this->retryAfter);
            })
            ->where('available_at', '<=', time())
            ->orderBy('id', 'asc')
            ->first();

        return $job ? (object) $job : null;
    }

    /**
     * Marshal the reserved job into a DatabaseJob instance.
     *
     * @param string $queue
     * @param object $job
     * @return DatabaseJob
     */
    protected function marshalJob(string $queue, object $job): DatabaseJob
    {
        $job = $this->markJobAsReserved($job);

        return new DatabaseJob(
            app(),
            $this,
            $job,
            $queue
        );
    }

    /**
     * Mark the given job ID as reserved.
     *
     * @param object $job
     * @return object
     */
    protected function markJobAsReserved(object $job): object
    {
        $this->database->table($this->table)->where('id', $job->id)->update([
            'reserved_at' => time(),
            'attempts' => $job->attempts + 1,
        ]);

        $job->reserved_at = time();
        $job->attempts++;

        return $job;
    }

    /**
     * Create a payload string from the given job and data.
     *
     * @param object $job
     * @param mixed $data
     * @return string
     */
    protected function createPayload(object $job, $data = ''): string
    {
        return json_encode([
            'job' => get_class($job),
            'data' => serialize($job),
            'uuid' => (string) Str::uuid(),
        ]);
    }

    /**
     * Push a raw payload onto the database queue.
     *
     * @param string|null $queue
     * @param string $payload
     * @param int $delay
     * @return mixed
     */
    protected function pushToDatabase(?string $queue, string $payload, int $delay = 0)
    {
        return $this->database->table($this->table)->insert([
            'queue' => $queue ?: $this->default,
            'payload' => $payload,
            'attempts' => 0,
            'reserved_at' => null,
            'available_at' => time() + $delay,
            'created_at' => time(),
        ]);
    }

    /**
     * Delete a reserved job from the queue.
     *
     * @param string $id
     * @return void
     */
    public function deleteReserved(string $id): void
    {
        $this->database->table($this->table)->where('id', $id)->delete();
    }

    /**
     * Release a reserved job back onto the queue.
     *
     * @param string $queue
     * @param object $job
     * @param int $delay
     * @return void
     */
    public function release(string $queue, object $job, int $delay = 0): void
    {
        $this->database->table($this->table)->where('id', $job->id)->update([
            'reserved_at' => null,
            'available_at' => time() + $delay,
        ]);
    }

    /**
     * Get the size of the queue.
     *
     * @param string|null $queue
     * @return int
     */
    public function size(?string $queue = null): int
    {
        return $this->database->table($this->table)
            ->where('queue', $queue ?: $this->default)
            ->count();
    }
}
