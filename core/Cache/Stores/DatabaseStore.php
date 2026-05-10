<?php

declare(strict_types=1);

namespace Fly\Cache\Stores;

use Fly\Cache\CacheStoreInterface;
use Fly\Database\Connection;

class DatabaseStore implements CacheStoreInterface
{
    /**
     * The database connection instance.
     */
    protected Connection $connection;

    /**
     * The name of the cache table.
     */
    protected string $table;

    /**
     * A string that should be prepended to keys.
     */
    protected string $prefix;

    /**
     * Create a new database store instance.
     */
    public function __construct(Connection $connection, string $table, string $prefix = '')
    {
        $this->connection = $connection;
        $this->table = $table;
        $this->prefix = $prefix;
    }

    /**
     * Retrieve an item from the cache by key.
     */
    public function get(string $key): mixed
    {
        $cache = $this->table()->where('key', $this->prefix . $key)->first();

        if (is_null($cache)) {
            return null;
        }

        $cache = (object) $cache;

        if (time() >= $cache->expiration && $cache->expiration !== 0) {
            $this->forget($key);
            return null;
        }

        return unserialize($cache->value);
    }

    /**
     * Store an item in the cache for a given number of seconds.
     */
    public function put(string $key, mixed $value, int $seconds): bool
    {
        $key = $this->prefix . $key;
        $value = serialize($value);
        $expiration = $seconds > 0 ? time() + $seconds : 0;

        try {
            $this->table()->insert([
                'key' => $key,
                'value' => $value,
                'expiration' => $expiration,
            ]);
        } catch (\Exception $e) {
            $this->table()->where('key', $key)->update([
                'value' => $value,
                'expiration' => $expiration,
            ]);
        }

        return true;
    }

    /**
     * Increment the value of an item in the cache.
     */
    public function increment(string $key, int $value = 1): int|bool
    {
        return $this->connection->transaction(function () use ($key, $value) {
            $current = $this->get($key) ?: 0;
            $new = (int) $current + $value;
            $this->forever($key, $new);
            return $new;
        });
    }

    /**
     * Decrement the value of an item in the cache.
     */
    public function decrement(string $key, int $value = 1): int|bool
    {
        return $this->increment($key, $value * -1);
    }

    /**
     * Store an item in the cache indefinitely.
     */
    public function forever(string $key, mixed $value): bool
    {
        return $this->put($key, $value, 0);
    }

    /**
     * Remove an item from the cache.
     */
    public function forget(string $key): bool
    {
        $this->table()->where('key', $this->prefix . $key)->delete();
        return true;
    }

    /**
     * Remove all items from the cache.
     */
    public function flush(): bool
    {
        $this->table()->delete();
        return true;
    }

    /**
     * Get the cache key prefix.
     */
    public function getPrefix(): string
    {
        return $this->prefix;
    }

    /**
     * Get a query builder for the cache table.
     */
    protected function table()
    {
        return $this->connection->table($this->table);
    }
}
