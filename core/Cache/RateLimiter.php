<?php

declare(strict_types=1);

namespace Fly\Cache;

use Fly\Cache\CacheManager;

class RateLimiter
{
    /**
     * The cache manager instance.
     */
    protected CacheManager $cache;

    /**
     * Create a new rate limiter instance.
     */
    public function __construct(CacheManager $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Attempt to execute a callback if the rate limit is not exceeded.
     */
    public function attempt(string $key, int $maxAttempts, \Closure $callback, int $decaySeconds = 60): mixed
    {
        if ($this->tooManyAttempts($key, $maxAttempts)) {
            return false;
        }

        $result = $callback();

        $this->hit($key, $decaySeconds);

        return $result;
    }

    /**
     * Determine if the given key has been "throttled" too many times.
     */
    public function tooManyAttempts(string $key, int $maxAttempts): bool
    {
        if ($this->attempts($key) >= $maxAttempts) {
            if ($this->cache->has($key . ':timer')) {
                return true;
            }

            $this->resetAttempts($key);
        }

        return false;
    }

    /**
     * Increment the counter for a given key for a given decay time.
     */
    public function hit(string $key, int $decaySeconds = 60): int
    {
        $this->cache->add($key . ':timer', time() + $decaySeconds, $decaySeconds);

        $added = $this->cache->add($key, 0, $decaySeconds);

        $hits = (int) $this->cache->increment($key);

        if (!$added && $hits == 1) {
            $this->cache->put($key, 1, $decaySeconds);
        }

        return $hits;
    }

    /**
     * Get the number of attempts for the given key.
     */
    public function attempts(string $key): int
    {
        return (int) $this->cache->get($key, 0);
    }

    /**
     * Reset the number of attempts for the given key.
     */
    public function resetAttempts(string $key): bool
    {
        return $this->cache->forget($key) && $this->cache->forget($key . ':timer');
    }

    /**
     * Get the number of seconds until the "timer" expires.
     */
    public function availableIn(string $key): int
    {
        return max(0, $this->cache->get($key . ':timer') - time());
    }

    /**
     * Get the number of retries left for the given key.
     */
    public function retriesLeft(string $key, int $maxAttempts): int
    {
        $attempts = $this->attempts($key);

        return max(0, $maxAttempts - $attempts);
    }
}
