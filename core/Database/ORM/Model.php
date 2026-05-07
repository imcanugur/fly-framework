<?php

declare(strict_types=1);

namespace Fly\Database\ORM;

use ArrayAccess;
use JsonSerializable;
use Fly\Support\Facades\DB;
use Fly\Database\ORM\Relations\HasOne;
use Fly\Database\ORM\Relations\HasMany;
use Fly\Database\ORM\Relations\BelongsTo;

/**
 * Base ORM Model — Active Record Pattern.
 */
abstract class Model implements ArrayAccess, JsonSerializable
{
    use Concerns\HasEvents;

    /** @var string|null Override to set a custom table name. */
    protected ?string $table = null;

    /** @var string Primary key column name. */
    protected string $primaryKey = 'id';

    /** @var bool Whether the model exists in the database. */
    protected bool $exists = false;

    /** @var array<string, mixed> Current attributes. */
    protected array $attributes = [];

    /** @var array<string, mixed> Original attributes as loaded from DB. */
    protected array $original = [];

    /** @var bool Enable automatic timestamps. */
    public bool $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected array $fillable = [];

    /**
     * The attributes that are NOT mass assignable.
     *
     * @var array<int, string>
     */
    protected array $guarded = ['*'];

    /**
     * The attributes that should be hidden in serialization.
     *
     * @var array<int, string>
     */
    protected array $hidden = [];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected array $casts = [];

    /**
     * Computed attributes to append during serialization.
     *
     * @var array<int, string>
     */
    protected array $appends = [];

    /**
     * Relationships to eager-load on every query.
     *
     * @var array<int, string>
     */
    protected array $with = [];

    /**
     * Indicates if the model was recently created (INSERT, not UPDATE).
     */
    public bool $wasRecentlyCreated = false;

    /**
     * Loaded relationship data cache.
     *
     * @var array<string, mixed>
     */
    protected array $relations = [];

    /**
     * The array of booted models.
     */
    protected static array $booted = [];

    // ----------------------------------------------------------------
    // Construction & Booting
    // ----------------------------------------------------------------

    public function __construct(array $attributes = [])
    {
        $this->bootIfNotBooted();
        $this->fill($attributes);
    }

    protected function bootIfNotBooted(): void
    {
        if (!isset(static::$booted[static::class])) {
            static::$booted[static::class] = true;
            static::boot();
        }
    }

    protected static function boot(): void
    {
        if (method_exists(static::class, 'initializeSoftDeletes')) {
            (new static)->initializeSoftDeletes();
        }
    }

    public function getTable(): string
    {
        if ($this->table) return $this->table;
        $class = basename(str_replace('\\', '/', static::class));
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $class)) . 's';
    }

    public function getKeyName(): string { return $this->primaryKey; }
    public function getKey(): mixed { return $this->getAttribute($this->primaryKey); }

    // ----------------------------------------------------------------
    // Attribute Access
    // ----------------------------------------------------------------

    public function fill(array $attributes): self
    {
        foreach ($attributes as $key => $value) {
            if ($this->isFillable($key)) $this->setAttribute($key, $value);
        }
        return $this;
    }

    public function forceFill(array $attributes): self
    {
        foreach ($attributes as $key => $value) $this->setAttribute($key, $value);
        return $this;
    }

    public function isFillable(string $key): bool
    {
        if (!empty($this->fillable)) return in_array($key, $this->fillable, true);
        if ($this->guarded === ['*']) return true;
        return !in_array($key, $this->guarded, true);
    }

    public function setAttribute(string $key, mixed $value): void
    {
        $mutator = 'set' . $this->studly($key) . 'Attribute';
        if (method_exists($this, $mutator)) {
            $this->{$mutator}($value);
            return;
        }
        $this->attributes[$key] = $value;
    }

    public function getAttribute(string $key): mixed
    {
        $accessor = 'get' . $this->studly($key) . 'Attribute';
        if (method_exists($this, $accessor)) return $this->{$accessor}();
        if (array_key_exists($key, $this->relations)) return $this->relations[$key];
        $value = $this->attributes[$key] ?? null;
        if (isset($this->casts[$key])) return $this->castAttribute($key, $value);
        return $value;
    }

    public function isDirty(?string $key = null): bool
    {
        $dirty = $this->getDirty();
        return $key === null ? count($dirty) > 0 : array_key_exists($key, $dirty);
    }

    public function getDirty(): array
    {
        $dirty = [];
        foreach ($this->attributes as $key => $value) {
            if (!array_key_exists($key, $this->original) || $this->original[$key] !== $value) {
                $dirty[$key] = $value;
            }
        }
        return $dirty;
    }

    public function syncOriginal(): self { $this->original = $this->attributes; return $this; }

    public static function query(): Builder
    {
        $model = new static;
        return new Builder(DB::table($model->getTable()), $model);
    }

    public static function find(int|string $id): ?static
    {
        $model = new static;
        return static::query()->where($model->primaryKey, $id)->first();
    }

    public static function findOrFail(int|string $id): static
    {
        $model = static::find($id);
        if (!$model) throw new \RuntimeException("Model [" . static::class . "] not found for ID [{$id}].");
        return $model;
    }

    public static function all(): array { return static::query()->get(); }

    public static function create(array $attributes): static
    {
        $model = new static;
        $model->forceFill($attributes);
        $model->save();
        return $model;
    }

    public function save(): bool
    {
        if ($this->fireModelEvent('saving') === false) return false;

        if ($this->timestamps) {
            $now = date('Y-m-d H:i:s');
            $this->attributes['updated_at'] = $now;
            if (!$this->exists) $this->attributes['created_at'] = $now;
        }

        if ($this->exists) {
            if ($this->fireModelEvent('updating') === false) return false;
            $dirty = $this->getDirty();
            if (empty($dirty)) {
                $this->fireModelEvent('saved');
                return true;
            }
            DB::table($this->getTable())->where($this->primaryKey, $this->attributes[$this->primaryKey])->update($dirty);
            $this->fireModelEvent('updated');
        } else {
            if ($this->fireModelEvent('creating') === false) return false;
            $id = DB::table($this->getTable())->insertGetId($this->attributes);
            $this->attributes[$this->primaryKey] = $id;
            $this->exists = true;
            $this->wasRecentlyCreated = true;
            $this->fireModelEvent('created');
        }

        $this->syncOriginal();
        $this->fireModelEvent('saved');
        return true;
    }

    public function delete(): bool
    {
        if (!$this->exists) return false;
        if ($this->fireModelEvent('deleting') === false) return false;
        DB::table($this->getTable())->where($this->primaryKey, $this->attributes[$this->primaryKey])->delete();
        $this->exists = false;
        $this->fireModelEvent('deleted');
        return true;
    }

    public static function hydrate(array $attributes): static
    {
        $model = new static;
        $model->attributes = $attributes;
        $model->original = $attributes;
        $model->exists = true;
        return $model;
    }

    public static function creating(callable $callback): void { static::registerModelEvent('creating', $callback); }
    public static function created(callable $callback): void { static::registerModelEvent('created', $callback); }
    public static function updating(callable $callback): void { static::registerModelEvent('updating', $callback); }
    public static function updated(callable $callback): void { static::registerModelEvent('updated', $callback); }
    public static function saving(callable $callback): void { static::registerModelEvent('saving', $callback); }
    public static function saved(callable $callback): void { static::registerModelEvent('saved', $callback); }
    public static function deleting(callable $callback): void { static::registerModelEvent('deleting', $callback); }
    public static function deleted(callable $callback): void { static::registerModelEvent('deleted', $callback); }

    public function hasOne(string $related, ?string $foreignKey = null, ?string $localKey = null): HasOne
    {
        $instance = new $related;
        return new HasOne($instance, $this, $foreignKey ?: $this->getForeignKeyName(), $localKey ?: $this->primaryKey);
    }

    public function hasMany(string $related, ?string $foreignKey = null, ?string $localKey = null): HasMany
    {
        $instance = new $related;
        return new HasMany($instance, $this, $foreignKey ?: $this->getForeignKeyName(), $localKey ?: $this->primaryKey);
    }

    public function belongsTo(string $related, ?string $foreignKey = null, ?string $ownerKey = null): BelongsTo
    {
        $instance = new $related;
        return new BelongsTo($instance, $this, $foreignKey ?: strtolower(basename(str_replace('\\', '/', $related))) . '_id', $ownerKey ?: $instance->primaryKey);
    }

    protected function getForeignKeyName(): string
    {
        $class = basename(str_replace('\\', '/', static::class));
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $class)) . '_id';
    }

    public function toArray(): array
    {
        $attributes = $this->attributes;
        foreach ($this->relations as $key => $value) {
            $attributes[$key] = is_array($value) ? array_map(fn($m) => $m instanceof self ? $m->toArray() : $m, $value) : ($value instanceof self ? $value->toArray() : $value);
        }
        foreach ($this->appends as $key) $attributes[$key] = $this->getAttribute($key);
        foreach ($this->hidden as $hidden) unset($attributes[$hidden]);
        return $attributes;
    }

    public function toJson(int $options = 0): string { return json_encode($this->toArray(), $options | JSON_THROW_ON_ERROR); }
    public function jsonSerialize(): mixed { return $this->toArray(); }

    public function __get(string $key): mixed { return $this->getAttribute($key); }
    public function __set(string $key, mixed $value): void { $this->setAttribute($key, $value); }
    public function __isset(string $key): bool { return isset($this->attributes[$key]) || isset($this->relations[$key]); }

    public static function __callStatic(string $method, array $parameters): mixed { return (new static)->$method(...$parameters); }

    public function __call(string $method, array $parameters): mixed
    {
        $scopeMethod = 'scope' . ucfirst($method);
        if (method_exists($this, $scopeMethod)) {
            $query = static::query();
            $this->{$scopeMethod}($query, ...$parameters);
            return $query;
        }
        return static::query()->$method(...$parameters);
    }

    protected function studly(string $value): string { return str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $value))); }

    protected function castAttribute(string $key, mixed $value): mixed
    {
        if ($value === null) return null;
        return match ($this->casts[$key]) {
            'int', 'integer' => (int) $value,
            'float', 'double' => (float) $value,
            'string' => (string) $value,
            'bool', 'boolean' => (bool) $value,
            'array', 'json' => json_decode((string) $value, true),
            'datetime' => new \DateTimeImmutable((string) $value),
            default => $value,
        };
    }
}
