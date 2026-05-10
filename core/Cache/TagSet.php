<?php

declare(strict_types=1);

namespace Fly\Cache;

class TagSet
{
    /**
     * The cache store implementation.
     */
    protected CacheStoreInterface $store;

    /**
     * The names of the tags.
     */
    protected array $names = [];

    /**
     * Create a new tag set instance.
     */
    public function __construct(CacheStoreInterface $store, array $names = [])
    {
        $this->store = $store;
        $this->names = $names;
    }

    /**
     * Reset all tags in the set.
     */
    public function reset(): void
    {
        foreach ($this->names as $name) {
            $this->resetTag($name);
        }
    }

    /**
     * Reset the tag and return the new tag identifier.
     */
    public function resetTag(string $name): string
    {
        $id = str_replace('.', '', uniqid('', true));
        $this->store->forever($this->tagKey($name), $id);
        return $id;
    }

    /**
     * Get a unique identifier for the tag set.
     */
    public function getNamespace(): string
    {
        return implode('|', array_map([$this, 'tagId'], $this->names));
    }

    /**
     * Get the unique identifier for a tag.
     */
    public function tagId(string $name): string
    {
        return $this->store->get($this->tagKey($name)) ?: $this->resetTag($name);
    }

    /**
     * Get the cache key for a tag.
     */
    protected function tagKey(string $name): string
    {
        return 'tag:' . $name . ':key';
    }
}
