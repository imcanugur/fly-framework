<?php

declare(strict_types=1);

namespace Fly\Database\ORM;

use ArrayAccess;
use JsonSerializable;
use Fly\Support\Facades\DB;

/**
 * Active Record Pattern Implementation
 */
abstract class Model implements ArrayAccess, JsonSerializable
{
    protected string $table;
    protected string $primaryKey = 'id';
    protected array $attributes = [];
    protected array $original = [];
    protected bool $exists = false;

    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
    }

    public function getTable(): string
    {
        return $this->table ?? strtolower(basename(str_replace('\\', '/', static::class))) . 's';
    }

    public function fill(array $attributes): self
    {
        foreach ($attributes as $key => $value) {
            $this->setAttribute($key, $value);
        }
        return $this;
    }

    public function setAttribute(string $key, mixed $value): void
    {
        $this->attributes[$key] = $value;
    }

    public function getAttribute(string $key): mixed
    {
        return $this->attributes[$key] ?? null;
    }

    public static function query(): \Fly\Database\Query\Builder
    {
        return DB::table((new static)->getTable());
    }

    public static function find(int|string $id): ?static
    {
        $record = static::query()->where((new static)->primaryKey, (string)$id)->first();
        if (!$record) {
            return null;
        }
        return static::hydrate((array) $record);
    }

    public static function all(): array
    {
        $records = static::query()->get();
        return array_map(fn($r) => static::hydrate((array)$r), $records);
    }

    public static function create(array $attributes): static
    {
        $model = new static($attributes);
        $model->save();
        return $model;
    }

    public static function hydrate(array $attributes): static
    {
        $model = new static($attributes);
        $model->exists = true;
        $model->original = $attributes;
        return $model;
    }

    public function save(): bool
    {
        if (!$this->exists) {
            // Phase 9.3: creating event hook could go here
            $id = static::query()->insertGetId($this->attributes);
            if ($id) {
                $this->setAttribute($this->primaryKey, $id);
                $this->exists = true;
                $this->original = $this->attributes;
            }
        } else {
            // Phase 9.3: updating event hook could go here
            static::query()->where($this->primaryKey, (string)$this->attributes[$this->primaryKey])->update($this->attributes);
            $this->original = $this->attributes;
        }
        return true;
    }

    public function delete(): bool
    {
        if ($this->exists) {
            // Phase 9.3: deleting event hook could go here
            static::query()->where($this->primaryKey, (string)$this->attributes[$this->primaryKey])->delete();
            $this->exists = false;
        }
        return true;
    }

    public function __get(string $key)
    {
        return $this->getAttribute($key);
    }

    public function __set(string $key, $value)
    {
        $this->setAttribute($key, $value);
    }

    public function offsetExists(mixed $offset): bool { return isset($this->attributes[$offset]); }
    public function offsetGet(mixed $offset): mixed { return $this->getAttribute($offset); }
    public function offsetSet(mixed $offset, mixed $value): void { $this->setAttribute($offset, $value); }
    public function offsetUnset(mixed $offset): void { unset($this->attributes[$offset]); }

    public function jsonSerialize(): mixed { return $this->attributes; }
}
