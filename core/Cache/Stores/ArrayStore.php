<?php

declare(strict_types=1);

namespace Fly\Cache\Stores;

use Fly\Cache\CacheStoreInterface;

class ArrayStore implements CacheStoreInterface
{
    /**
     * The array of cached items.
     */
    protected array $storage = [];

    /**
     * Retrieve an item from the cache by key.
     */
    public function get(string $key): mixed
    {
        if (!isset($this->storage[$key])) {
            return null;
        }

        $item = $this->storage[$key];

        if ($item['expire'] !== 0 && time() >= $item['expire']) {
            $this->forget($key);
            return null;
        }

        return $item['value'];
    }

    /**
     * Store an item in the cache for a given number of seconds.
     */
    public function put(string $key, mixed $value, int $seconds): bool
    {
        $this->storage[$key] = [
            'value' => $value,
            'expire' => $seconds > 0 ? time() + $seconds : 0,
        ];

        return true;
    }

    /**
     * Increment the value of an item in the cache.
     */
    public function increment(string $key, int $value = 1): int|bool
    {
        $current = $this->get($key) ?: 0;
        $new = $current + $value;
        $this->forever($key, $new);
        return $new;
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
        unset($this->storage[$key]);
        return true;
    }

    /**
     * Remove all items from the cache.
     */
    public function flush(): bool
    {
        $this->storage = [];
        return true;
    }

    /**
     * Get the cache key prefix.
     */
    public function getPrefix(): string
    {
        return '';
    }
}
