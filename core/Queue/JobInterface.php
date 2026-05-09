<?php

declare(strict_types=1);

namespace Fly\Queue;

interface JobInterface
{
    /**
     * Fire the job.
     *
     * @return void
     */
    public function fire(): void;

    /**
     * Delete the job from the queue.
     *
     * @return void
     */
    public function delete(): void;

    /**
     * Release the job back into the queue.
     *
     * @param int $delay
     * @return void
     */
    public function release(int $delay = 0): void;

    /**
     * Get the number of times the job has been attempted.
     *
     * @return int
     */
    public function attempts(): int;

    /**
     * Get the job identifier.
     *
     * @return string
     */
    public function getId(): string;

    /**
     * Get the name of the queue the job belongs to.
     *
     * @return string
     */
    public function getQueue(): string;

    /**
     * Get the raw body of the job.
     *
     * @return string
     */
    public function getRawBody(): string;
}
