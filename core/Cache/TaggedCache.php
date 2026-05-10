<?php

declare(strict_types=1);

namespace Fly\Cache;

class TaggedCache extends Repository
{
    /**
     * The tag set instance.
     */
    protected TagSet $tags;

    /**
     * Create a new tagged cache instance.
     */
    public function __construct(CacheStoreInterface $store, TagSet $tags)
    {
        parent::__construct($store);
        $this->tags = $tags;
    }

    /**
     * Store an item in the cache.
     */
    public function put(string $key, mixed $value, int|\DateTimeInterface $ttl = null): bool
    {
        return $this->store->put($this->taggedKey($key), $value, $this->getSeconds($ttl));
    }

    /**
     * Retrieve an item from the cache by key.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return parent::get($this->taggedKey($key), $default);
    }

    /**
     * Remove an item from the cache.
     */
    public function forget(string $key): bool
    {
        return $this->store->forget($this->taggedKey($key));
    }

    /**
     * Remove all items from the cache for the tags.
     */
    public function flush(): bool
    {
        $this->tags->reset();
        return true;
    }

    /**
     * Get the tagged cache key.
     */
    protected function taggedKey(string $key): string
    {
        return sha1($this->tags->getNamespace()) . ':' . $key;
    }
}
