<?php

declare(strict_types=1);

namespace Fly\Cache\Stores;

interface LockInterface
{
    /**
     * Attempt to acquire the lock.
     */
    public function get(?callable $callback = null): bool;

    /**
     * Attempt to acquire the lock for the given number of seconds.
     */
    public function block(int $seconds, ?callable $callback = null): bool;

    /**
     * Release the lock.
     */
    public function release(): bool;

    /**
     * Returns the current owner of the lock.
     */
    public function owner(): string;

    /**
     * Forces the lock to be released.
     */
    public function forceRelease(): void;
}
