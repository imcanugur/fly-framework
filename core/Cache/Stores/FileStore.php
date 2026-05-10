<?php

declare(strict_types=1);

namespace Fly\Cache\Stores;

use Fly\Cache\CacheStoreInterface;

class FileStore implements CacheStoreInterface
{
    /**
     * The path where the cache files are stored.
     */
    protected string $directory;

    /**
     * Create a new file store instance.
     */
    public function __construct(string $directory)
    {
        $this->directory = $directory;

        if (!is_dir($this->directory)) {
            mkdir($this->directory, 0755, true);
        }
    }

    /**
     * Retrieve an item from the cache by key.
     */
    public function get(string $key): mixed
    {
        $path = $this->getPath($key);

        if (!file_exists($path)) {
            return null;
        }

        $content = file_get_contents($path);
        $expire = (int) substr($content, 0, 10);

        if (time() >= $expire && $expire !== 0) {
            $this->forget($key);
            return null;
        }

        return unserialize(substr($content, 10));
    }

    /**
     * Store an item in the cache for a given number of seconds.
     */
    public function put(string $key, mixed $value, int $seconds): bool
    {
        $expire = $seconds > 0 ? time() + $seconds : 0;
        $content = str_pad((string) $expire, 10, '0', STR_PAD_LEFT) . serialize($value);

        return (bool) file_put_contents($this->getPath($key), $content);
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
        $path = $this->getPath($key);

        if (file_exists($path)) {
            return unlink($path);
        }

        return false;
    }

    /**
     * Remove all items from the cache.
     */
    public function flush(): bool
    {
        foreach (glob($this->directory . '/*') as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        return true;
    }

    /**
     * Get the cache key prefix.
     */
    public function getPrefix(): string
    {
        return '';
    }

    /**
     * Get a lock instance.
     */
    public function lock(string $name, int $seconds = 0, string $owner = ''): FileLock
    {
        return new FileLock($this->directory . '/' . md5($name) . '.lock', $seconds, $owner);
    }

    /**
     * Get the full path for the given cache key.
     */
    protected function getPath(string $key): string
    {
        $hash = md5($key);
        return $this->directory . '/' . $hash;
    }
}
