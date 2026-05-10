<?php

declare(strict_types=1);

namespace Fly\Support\Facades;

/**
 * @method static mixed get(string $key, mixed $default = null)
 * @method static bool put(string $key, mixed $value, int|\DateTimeInterface $ttl = null)
 * @method static bool forget(string $key)
 * @method static bool flush()
 * @method static \Fly\Cache\TaggedCache tags(array|string $names)
 * @method static mixed remember(string $key, int|\DateTimeInterface $ttl, \Closure $callback)
 * 
 * @see \Fly\Cache\CacheManager
 * @see \Fly\Cache\Repository
 */
class Cache extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'cache';
    }
}
