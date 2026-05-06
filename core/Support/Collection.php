<?php

declare(strict_types=1);

namespace Fly\Support;

use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use JsonSerializable;

/**
 * Fluent Collection — wraps arrays with a chainable API.
 *
 * Provides map, filter, reduce, each, pluck, sort, group, chunk,
 * first, last, flatten, unique, contains, sum, avg, min, max,
 * toArray, toJson, and more.
 */
class Collection implements ArrayAccess, Countable, IteratorAggregate, JsonSerializable
{
    /**
     * @param array<int|string, mixed> $items
     */
    public function __construct(protected array $items = []) {}

    /**
     * Create a new collection instance.
     */
    public static function make(array $items = []): static
    {
        return new static($items);
    }

    /**
     * Get all items.
     */
    public function all(): array
    {
        return $this->items;
    }

    /**
     * Run a map over each item.
     */
    public function map(callable $callback): static
    {
        return new static(array_map($callback, $this->items, array_keys($this->items)));
    }

    /**
     * Filter items by a callback.
     */
    public function filter(?callable $callback = null): static
    {
        if ($callback) {
            return new static(array_filter($this->items, $callback, ARRAY_FILTER_USE_BOTH));
        }
        return new static(array_filter($this->items));
    }

    /**
     * Filter items where a key equals a value.
     */
    public function where(string $key, mixed $value): static
    {
        return $this->filter(fn($item) => data_get($item, $key) === $value);
    }

    /**
     * Reduce the collection to a single value.
     */
    public function reduce(callable $callback, mixed $initial = null): mixed
    {
        return array_reduce($this->items, $callback, $initial);
    }

    /**
     * Execute a callback over each item.
     */
    public function each(callable $callback): static
    {
        foreach ($this->items as $key => $item) {
            if ($callback($item, $key) === false) {
                break;
            }
        }
        return $this;
    }

    /**
     * Get the values of a given key.
     */
    public function pluck(string $value, ?string $key = null): static
    {
        $results = [];
        foreach ($this->items as $item) {
            $itemValue = data_get($item, $value);
            if ($key !== null) {
                $results[data_get($item, $key)] = $itemValue;
            } else {
                $results[] = $itemValue;
            }
        }
        return new static($results);
    }

    /**
     * Sort the collection.
     */
    public function sortBy(string|callable $callback, bool $descending = false): static
    {
        $items = $this->items;

        if (is_string($callback)) {
            $key = $callback;
            $callback = fn($a, $b) => data_get($a, $key) <=> data_get($b, $key);
        }

        usort($items, $callback);

        if ($descending) {
            $items = array_reverse($items);
        }

        return new static($items);
    }

    /**
     * Sort the collection in descending order.
     */
    public function sortByDesc(string|callable $callback): static
    {
        return $this->sortBy($callback, true);
    }

    /**
     * Group the collection by a key.
     */
    public function groupBy(string|callable $groupBy): static
    {
        $results = [];
        foreach ($this->items as $item) {
            $key = is_callable($groupBy) ? $groupBy($item) : data_get($item, $groupBy);
            $results[$key][] = $item;
        }
        return new static(array_map(fn($g) => new static($g), $results));
    }

    /**
     * Chunk the collection into pieces.
     */
    public function chunk(int $size): static
    {
        $chunks = [];
        foreach (array_chunk($this->items, $size, true) as $chunk) {
            $chunks[] = new static($chunk);
        }
        return new static($chunks);
    }

    /**
     * Get the first item.
     */
    public function first(?callable $callback = null, mixed $default = null): mixed
    {
        if ($callback === null) {
            return $this->items[array_key_first($this->items)] ?? $default;
        }
        foreach ($this->items as $key => $item) {
            if ($callback($item, $key)) {
                return $item;
            }
        }
        return $default;
    }

    /**
     * Get the last item.
     */
    public function last(?callable $callback = null, mixed $default = null): mixed
    {
        if ($callback === null) {
            return $this->items[array_key_last($this->items)] ?? $default;
        }
        $result = $default;
        foreach ($this->items as $key => $item) {
            if ($callback($item, $key)) {
                $result = $item;
            }
        }
        return $result;
    }

    /**
     * Flatten a multi-dimensional collection.
     */
    public function flatten(int $depth = INF): static
    {
        $result = [];
        foreach ($this->items as $item) {
            if (!is_array($item) && !$item instanceof static) {
                $result[] = $item;
            } elseif ($depth === 1) {
                $result = array_merge($result, $item instanceof static ? $item->all() : $item);
            } else {
                $result = array_merge($result, static::make($item instanceof static ? $item->all() : $item)->flatten($depth - 1)->all());
            }
        }
        return new static($result);
    }

    /**
     * Return only unique items.
     */
    public function unique(?string $key = null): static
    {
        if ($key === null) {
            return new static(array_values(array_unique($this->items, SORT_REGULAR)));
        }
        $seen = [];
        $result = [];
        foreach ($this->items as $item) {
            $val = data_get($item, $key);
            if (!in_array($val, $seen, true)) {
                $seen[] = $val;
                $result[] = $item;
            }
        }
        return new static($result);
    }

    /**
     * Get the values of the collection (re-index).
     */
    public function values(): static
    {
        return new static(array_values($this->items));
    }

    /**
     * Get the keys of the collection.
     */
    public function keys(): static
    {
        return new static(array_keys($this->items));
    }

    /**
     * Merge another array or collection.
     */
    public function merge(array|self $items): static
    {
        $merge = $items instanceof self ? $items->all() : $items;
        return new static(array_merge($this->items, $merge));
    }

    /**
     * Push an item onto the end.
     */
    public function push(mixed ...$values): static
    {
        foreach ($values as $value) {
            $this->items[] = $value;
        }
        return $this;
    }

    /**
     * Determine if an item exists.
     */
    public function contains(mixed $key, mixed $value = null): bool
    {
        if ($value !== null) {
            return $this->pluck($key)->contains($value);
        }
        if (is_callable($key)) {
            foreach ($this->items as $k => $item) {
                if ($key($item, $k)) {
                    return true;
                }
            }
            return false;
        }
        return in_array($key, $this->items, false);
    }

    /**
     * Determine if the collection is empty.
     */
    public function isEmpty(): bool
    {
        return empty($this->items);
    }

    /**
     * Determine if the collection is not empty.
     */
    public function isNotEmpty(): bool
    {
        return !$this->isEmpty();
    }

    // ----------------------------------------------------------------
    // Aggregates
    // ----------------------------------------------------------------

    public function sum(string|callable|null $callback = null): int|float
    {
        if ($callback === null) {
            return array_sum($this->items);
        }
        if (is_string($callback)) {
            return array_sum($this->pluck($callback)->all());
        }
        return array_sum($this->map($callback)->all());
    }

    public function avg(string|callable|null $callback = null): int|float
    {
        $count = $this->count();
        return $count ? $this->sum($callback) / $count : 0;
    }

    public function min(string|callable|null $callback = null): mixed
    {
        $items = $callback ? $this->pluck($callback)->all() : $this->items;
        return min($items);
    }

    public function max(string|callable|null $callback = null): mixed
    {
        $items = $callback ? $this->pluck($callback)->all() : $this->items;
        return max($items);
    }

    /**
     * Implode values.
     */
    public function implode(string $glue, ?string $key = null): string
    {
        if ($key !== null) {
            return implode($glue, $this->pluck($key)->all());
        }
        return implode($glue, $this->items);
    }

    /**
     * Get a slice of the collection.
     */
    public function slice(int $offset, ?int $length = null): static
    {
        return new static(array_slice($this->items, $offset, $length, true));
    }

    /**
     * Take the first or last {limit} items.
     */
    public function take(int $limit): static
    {
        if ($limit < 0) {
            return $this->slice($limit, abs($limit));
        }
        return $this->slice(0, $limit);
    }

    /**
     * Apply the callback if the value is truthy.
     */
    public function when(mixed $value, callable $callback, ?callable $default = null): static
    {
        if ($value) {
            return $callback($this, $value);
        } elseif ($default) {
            return $default($this, $value);
        }
        return $this;
    }

    /**
     * Pipe the collection through a callback.
     */
    public function pipe(callable $callback): mixed
    {
        return $callback($this);
    }

    /**
     * Tap into the collection (for debugging).
     */
    public function tap(callable $callback): static
    {
        $callback($this);
        return $this;
    }

    /**
     * Create a collection of number range.
     */
    public static function range(int $from, int $to): static
    {
        return new static(range($from, $to));
    }

    /**
     * Combine keys and values.
     */
    public function combine(array|self $values): static
    {
        $vals = $values instanceof self ? $values->all() : $values;
        return new static(array_combine($this->items, $vals));
    }

    /**
     * Flip the keys and values.
     */
    public function flip(): static
    {
        return new static(array_flip($this->items));
    }

    // ----------------------------------------------------------------
    // Serialization & Interfaces
    // ----------------------------------------------------------------

    public function toArray(): array
    {
        return array_map(function ($value) {
            if ($value instanceof JsonSerializable) {
                return $value->jsonSerialize();
            }
            if ($value instanceof self) {
                return $value->toArray();
            }
            return $value;
        }, $this->items);
    }

    public function toJson(int $options = 0): string
    {
        return json_encode($this->toArray(), $options | JSON_THROW_ON_ERROR);
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->items);
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->items[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->items[$offset];
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($offset === null) {
            $this->items[] = $value;
        } else {
            $this->items[$offset] = $value;
        }
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->items[$offset]);
    }
}

// ----------------------------------------------------------------
// Global Helper
// ----------------------------------------------------------------
if (!function_exists('data_get')) {
    /**
     * Get an item from an array or object using dot notation.
     */
    function data_get(mixed $target, string $key, mixed $default = null): mixed
    {
        if ($key === '') {
            return $target;
        }

        foreach (explode('.', $key) as $segment) {
            if (is_array($target) && array_key_exists($segment, $target)) {
                $target = $target[$segment];
            } elseif (is_object($target) && isset($target->{$segment})) {
                $target = $target->{$segment};
            } else {
                return $default;
            }
        }

        return $target;
    }
}

if (!function_exists('collect')) {
    /**
     * Create a new Collection from the given items.
     */
    function collect(array $items = []): Collection
    {
        return new Collection($items);
    }
}
