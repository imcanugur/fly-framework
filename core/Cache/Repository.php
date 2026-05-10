<?php

declare(strict_types=1);

namespace Fly\Cache;

use Closure;
use DateTimeInterface;

class Repository implements CacheRepositoryInterface
{
    /**
     * The cache store implementation.
     */
    protected CacheStoreInterface $store;

    /**
     * Create a new cache repository instance.
     */
    public function __construct(CacheStoreInterface $store)
    {
        $this->store = $store;
    }

    /**
     * Determine if an item exists in the cache.
     */
    public function has(string $key): bool
    {
        return !is_null($this->get($key));
    }

    /**
     * Retrieve an item from the cache by key.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->store->get($key);

        if (is_null($value)) {
            $this->fireEvent(new Events\CacheMissed($key));
            return $default instanceof Closure ? $default() : $default;
        }

        $this->fireEvent(new Events\CacheHit($key, $value));
        return $value;
    }

    /**
     * Retrieve an item from the cache and delete it.
     */
    public function pull(string $key, mixed $default = null): mixed
    {
        $value = $this->get($key, $default);
        $this->forget($key);
        return $value;
    }

    /**
     * Store an item in the cache.
     */
    public function put(string $key, mixed $value, int|DateTimeInterface $ttl = null): bool
    {
        if (is_null($ttl)) {
            return $this->forever($key, $value);
        }

        $seconds = $this->getSeconds($ttl);

        if ($seconds <= 0) {
            return $this->forget($key);
        }

        $result = $this->store->put($key, $value, $seconds);

        if ($result) {
            $this->fireEvent(new Events\KeyWritten($key, $value, $seconds));
        }

        return $result;
    }

    /**
     * Store an item in the cache if the key does not exist.
     */
    public function add(string $key, mixed $value, int|DateTimeInterface $ttl = null): bool
    {
        if (is_null($this->get($key))) {
            return $this->put($key, $value, $ttl);
        }

        return false;
    }

    /**
     * Store an item in the cache indefinitely.
     */
    public function forever(string $key, mixed $value): bool
    {
        $result = $this->store->forever($key, $value);

        if ($result) {
            $this->fireEvent(new Events\KeyWritten($key, $value));
        }

        return $result;
    }

    /**
     * Get an item from the cache, or execute the given Closure and store the result.
     */
    public function remember(string $key, int|DateTimeInterface $ttl, Closure $callback): mixed
    {
        $value = $this->get($key);

        if (!is_null($value)) {
            return $value;
        }

        $this->put($key, $value = $callback(), $ttl);

        return $value;
    }

    /**
     * Get an item from the cache, or execute the given Closure and store the result forever.
     */
    public function rememberForever(string $key, Closure $callback): mixed
    {
        $value = $this->get($key);

        if (!is_null($value)) {
            return $value;
        }

        $this->forever($key, $value = $callback());

        return $value;
    }

    /**
     * Remove an item from the cache.
     */
    public function forget(string $key): bool
    {
        $result = $this->store->forget($key);

        if ($result) {
            $this->fireEvent(new Events\KeyForgotten($key));
        }

        return $result;
    }

    /**
     * Fire a cache event.
     */
    protected function fireEvent(object $event): void
    {
        if (function_exists('app') && app()->has('events')) {
            app('events')->dispatch($event);
        }
    }

    /**
     * Calculate the number of seconds for the given TTL.
     */
    protected function getSeconds(int|DateTimeInterface $ttl): int
    {
        if ($ttl instanceof DateTimeInterface) {
            return max(0, $ttl->getTimestamp() - time());
        }

        return $ttl;
    }

    /**
     * Get the cache store implementation.
     */
    public function getStore(): CacheStoreInterface
    {
        return $this->store;
    }

    /**
     * Begin executing a new tags operation.
     */
    public function tags(array|string $names): TaggedCache
    {
        return new TaggedCache($this->store, new TagSet($this->store, (array) $names));
    }

    /**
     * Handle dynamic calls into the store.
     */
    public function __call(string $method, array $parameters): mixed
    {
        return $this->store->$method(...$parameters);
    }
}
