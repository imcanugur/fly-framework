<?php

declare(strict_types=1);

namespace Fly\Cache;

use Closure;

interface CacheRepositoryInterface
{
    /**
     * Determine if an item exists in the cache.
     */
    public function has(string $key): bool;

    /**
     * Retrieve an item from the cache by key.
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Retrieve an item from the cache and delete it.
     */
    public function pull(string $key, mixed $default = null): mixed;

    /**
     * Store an item in the cache.
     */
    public function put(string $key, mixed $value, int|\DateTimeInterface $ttl = null): bool;

    /**
     * Store an item in the cache if the key does not exist.
     */
    public function add(string $key, mixed $value, int|\DateTimeInterface $ttl = null): bool;

    /**
     * Store an item in the cache indefinitely.
     */
    public function forever(string $key, mixed $value): bool;

    /**
     * Get an item from the cache, or execute the given Closure and store the result.
     */
    public function remember(string $key, int|\DateTimeInterface $ttl, Closure $callback): mixed;

    /**
     * Get an item from the cache, or execute the given Closure and store the result forever.
     */
    public function rememberForever(string $key, Closure $callback): mixed;

    /**
     * Remove an item from the cache.
     */
    public function forget(string $key): bool;

    /**
     * Get the cache store implementation.
     */
    public function getStore(): CacheStoreInterface;
}
