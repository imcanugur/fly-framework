<?php

declare(strict_types=1);

namespace Fly\Cache\Stores;

use Fly\Cache\CacheStoreInterface;

class RedisStore implements CacheStoreInterface
{
    /**
     * The Redis instance.
     */
    protected $redis;

    /**
     * A string that should be prepended to keys.
     */
    protected string $prefix;

    /**
     * Create a new Redis store instance.
     */
    public function __construct($redis, string $prefix = '')
    {
        $this->redis = $redis;
        $this->prefix = $prefix;
    }

    /**
     * Retrieve an item from the cache by key.
     */
    public function get(string $key): mixed
    {
        $value = $this->redis->get($this->prefix . $key);

        return !is_null($value) && $value !== false ? unserialize($value) : null;
    }

    /**
     * Store an item in the cache for a given number of seconds.
     */
    public function put(string $key, mixed $value, int $seconds): bool
    {
        return (bool) $this->redis->setex(
            $this->prefix . $key, $seconds, serialize($value)
        );
    }

    /**
     * Increment the value of an item in the cache.
     */
    public function increment(string $key, int $value = 1): int|bool
    {
        return $this->redis->incrBy($this->prefix . $key, $value);
    }

    /**
     * Decrement the value of an item in the cache.
     */
    public function decrement(string $key, int $value = 1): int|bool
    {
        return $this->redis->decrBy($this->prefix . $key, $value);
    }

    /**
     * Store an item in the cache indefinitely.
     */
    public function forever(string $key, mixed $value): bool
    {
        return (bool) $this->redis->set($this->prefix . $key, serialize($value));
    }

    /**
     * Remove an item from the cache.
     */
    public function forget(string $key): bool
    {
        return (bool) $this->redis->del($this->prefix . $key);
    }

    /**
     * Remove all items from the cache.
     */
    public function flush(): bool
    {
        return (bool) $this->redis->flushDb();
    }

    /**
     * Get the cache key prefix.
     */
    public function getPrefix(): string
    {
        return $this->prefix;
    }
}
