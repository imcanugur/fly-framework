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
 *
 * Features:
 * - CRUD: create, find, findOrFail, all, save, delete, destroy
 * - Dirty tracking: isDirty, getDirty, getOriginal
 * - Relationships: hasOne, hasMany, belongsTo
 * - Soft Deletes: via SoftDeletes trait
 * - Scopes: local scopes via scope{Name} methods
 * - Timestamps: automatic created_at / updated_at management
 * - Mass assignment protection: $fillable / $guarded
 * - Accessors & Mutators: get{Attr}Attribute / set{Attr}Attribute
 * - Serialization: toArray, toJson, jsonSerialize
 */
abstract class Model implements ArrayAccess, JsonSerializable
{
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
     * If empty, $guarded is used instead.
     *
     * @var array<int, string>
     */
    protected array $fillable = [];

    /**
     * The attributes that are NOT mass assignable.
     * Default: guard everything. Override $fillable or set $guarded = [].
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

    /**
     * The array of model event callbacks.
     */
    protected static array $dispatcher = [];

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

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        // Override to boot traits or setup things (like SoftDeletes)
        if (method_exists(static::class, 'initializeSoftDeletes')) {
            (new static)->initializeSoftDeletes();
        }
    }

    /**
     * Get the table associated with the model.
     */
    public function getTable(): string
    {
        if ($this->table) {
            return $this->table;
        }

        // Convert "App\Models\UserProfile" → "user_profiles"
        $class = basename(str_replace('\\', '/', static::class));
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $class)) . 's';
    }

    public function getKeyName(): string
    {
        return $this->primaryKey;
    }

    public function getKey(): mixed
    {
        return $this->getAttribute($this->primaryKey);
    }

    // ----------------------------------------------------------------
    // Attribute Access
    // ----------------------------------------------------------------

    /**
     * Fill the model with an array of attributes (mass assignment).
     */
    public function fill(array $attributes): self
    {
        foreach ($attributes as $key => $value) {
            if ($this->isFillable($key)) {
                $this->setAttribute($key, $value);
            }
        }
        return $this;
    }

    /**
     * Force-fill (bypasses mass-assignment protection).
     */
    public function forceFill(array $attributes): self
    {
        foreach ($attributes as $key => $value) {
            $this->setAttribute($key, $value);
        }
        return $this;
    }

    /**
     * Determine if the given attribute may be mass assigned.
     */
    public function isFillable(string $key): bool
    {
        // If fillable is defined, only allow those keys
        if (!empty($this->fillable)) {
            return in_array($key, $this->fillable, true);
        }

        // If guarded is ['*'], block everything via fill()
        if ($this->guarded === ['*']) {
            // Allow anyway for internal usage (hydrate, forceFill)
            return true;
        }

        // Otherwise block explicitly guarded keys
        return !in_array($key, $this->guarded, true);
    }

    public function setAttribute(string $key, mixed $value): void
    {
        // Check for mutator: setNameAttribute($value)
        $mutator = 'set' . $this->studly($key) . 'Attribute';
        if (method_exists($this, $mutator)) {
            $this->{$mutator}($value);
            return;
        }

        $this->attributes[$key] = $value;
    }

    public function getAttribute(string $key): mixed
    {
        // Check for accessor: getNameAttribute()
        $accessor = 'get' . $this->studly($key) . 'Attribute';
        if (method_exists($this, $accessor)) {
            return $this->{$accessor}();
        }

        // Check loaded relations
        if (array_key_exists($key, $this->relations)) {
            return $this->relations[$key];
        }

        $value = $this->attributes[$key] ?? null;

        // Apply casts
        if (isset($this->casts[$key])) {
            return $this->castAttribute($key, $value);
        }

        return $value;
    }

    /**
     * Get a raw attribute without accessors/casts.
     */
    public function getRawAttribute(string $key): mixed
    {
        return $this->attributes[$key] ?? null;
    }

    /**
     * Get all current attributes.
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    // ----------------------------------------------------------------
    // Dirty Tracking
    // ----------------------------------------------------------------

    /**
     * Determine if the model or given attribute has been modified.
     */
    public function isDirty(?string $key = null): bool
    {
        $dirty = $this->getDirty();

        if ($key === null) {
            return count($dirty) > 0;
        }

        return array_key_exists($key, $dirty);
    }

    /**
     * Get the attributes that have been changed since last sync.
     *
     * @return array<string, mixed>
     */
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

    /**
     * Get the original value of an attribute.
     */
    public function getOriginal(?string $key = null): mixed
    {
        if ($key === null) {
            return $this->original;
        }
        return $this->original[$key] ?? null;
    }

    /**
     * Sync the original attributes with the current.
     */
    public function syncOriginal(): self
    {
        $this->original = $this->attributes;
        return $this;
    }

    // ----------------------------------------------------------------
    // Query Builder Bridge
    // ----------------------------------------------------------------

    /**
     * Begin an ORM query for this model.
     */
    public static function query(): Builder
    {
        $model = new static;
        $queryBuilder = DB::table($model->getTable());
        return new Builder($queryBuilder, $model);
    }

    // ----------------------------------------------------------------
    // CRUD Operations
    // ----------------------------------------------------------------

    /**
     * Find a model by its primary key.
     */
    public static function find(int|string $id): ?static
    {
        $model = new static;
        return static::query()->where($model->primaryKey, $id)->first();
    }

    /**
     * Find a model by its primary key or throw an exception.
     */
    public static function findOrFail(int|string $id): static
    {
        $model = static::find($id);
        if ($model === null) {
            throw new \RuntimeException(
                "Model [" . static::class . "] not found for ID [{$id}]."
            );
        }
        return $model;
    }

    /**
     * Get all models.
     *
     * @return array<int, static>
     */
    public static function all(): array
    {
        return static::query()->get();
    }

    /**
     * Create a new model and persist it to the database.
     */
    public static function create(array $attributes): static
    {
        $model = new static;
        $model->forceFill($attributes);
        $model->save();
        return $model;
    }

    /**
     * Update or create a model matching the attributes.
     */
    public static function updateOrCreate(array $search, array $values = []): static
    {
        $instance = static::query();
        foreach ($search as $key => $val) {
            $instance = $instance->where($key, $val);
        }
        $model = $instance->first();

        if ($model) {
            $model->forceFill($values)->save();
            return $model;
        }

        return static::create(array_merge($search, $values));
    }

    /**
     * Destroy models by their primary keys.
     */
    public static function destroy(int|string ...$ids): int
    {
        $count = 0;
        foreach ($ids as $id) {
            $model = static::find($id);
            if ($model) {
                $model->delete();
                $count++;
            }
        }
        return $count;
    }

    /**
     * Save the model to the database.
     */
    public function save(): bool
    {
        if ($this->fireModelEvent('saving') === false) {
            return false;
        }

        if ($this->timestamps) {
            $now = date('Y-m-d H:i:s');
            $this->attributes['updated_at'] = $now;
            if (!$this->exists) {
                $this->attributes['created_at'] = $now;
            }
        }

        if ($this->exists) {
            if ($this->fireModelEvent('updating') === false) {
                return false;
            }

            // UPDATE: only send dirty attributes
            $dirty = $this->getDirty();
            if (empty($dirty)) {
                $this->fireModelEvent('updated');
                return true;
            }

            $qb = DB::table($this->getTable());
            $qb->where($this->primaryKey, $this->attributes[$this->primaryKey]);
            $qb->update($dirty);

            $this->fireModelEvent('updated');
        } else {
            if ($this->fireModelEvent('creating') === false) {
                return false;
            }

            // INSERT
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

    /**
     * Delete the model from the database.
     */
    public function delete(): bool
    {
        if (!$this->exists) {
            return false;
        }

        if ($this->fireModelEvent('deleting') === false) {
            return false;
        }

        DB::table($this->getTable())
            ->where($this->primaryKey, $this->attributes[$this->primaryKey])
            ->delete();

        $this->exists = false;
        $this->fireModelEvent('deleted');
        return true;
    }

    /**
     * Reload the model from the database.
     */
    public function fresh(): ?static
    {
        if (!$this->exists) {
            return null;
        }
        return static::find($this->getKey());
    }

    /**
     * Reload the model instance in-place.
     */
    public function refresh(): self
    {
        $fresh = $this->fresh();
        if ($fresh) {
            $this->attributes = $fresh->attributes;
            $this->original = $fresh->original;
            $this->relations = [];
        }
        return $this;
    }

    // ----------------------------------------------------------------
    // Hydration
    // ----------------------------------------------------------------

    /**
     * Create a model instance from a database record.
     */
    public static function hydrate(array $attributes): static
    {
        $model = new static;
        $model->attributes = $attributes;
        $model->original = $attributes;
        $model->exists = true;
        return $model;
    }

    /**
     * Create a new model instance without persisting.
     */
    public static function make(array $attributes = []): static
    {
        return new static($attributes);
    }

    /**
     * Determine if two models have the same ID and belong to the same table.
     */
    public function is(?Model $model): bool
    {
        return $model !== null &&
               $this->getKey() === $model->getKey() &&
               $this->getTable() === $model->getTable();
    }

    /**
     * Determine if two models are not the same.
     */
    public function isNot(?Model $model): bool
    {
        return !$this->is($model);
    }

    /**
     * Clone the model into a new, non-existing instance.
     */
    public function replicate(array $except = []): static
    {
        $attributes = $this->attributes;
        $except = array_merge([$this->getKeyName(), 'created_at', 'updated_at', 'deleted_at'], $except);

        foreach ($except as $key) {
            unset($attributes[$key]);
        }

        $instance = new static;
        $instance->setRawAttributes($attributes);
        return $instance;
    }

    public function setRawAttributes(array $attributes): self
    {
        $this->attributes = $attributes;
        return $this;
    }

    // ----------------------------------------------------------------
    // Events
    // ----------------------------------------------------------------

    protected function fireModelEvent(string $event): mixed
    {
        if (!isset(static::$dispatcher[static::class][$event])) {
            return true;
        }

        foreach (static::$dispatcher[static::class][$event] as $callback) {
            if ($callback($this) === false) {
                return false;
            }
        }

        return true;
    }

    public static function registerModelEvent(string $event, callable $callback): void
    {
        static::$dispatcher[static::class][$event][] = $callback;
    }

    public static function creating(callable $callback): void { static::registerModelEvent('creating', $callback); }
    public static function created(callable $callback): void { static::registerModelEvent('created', $callback); }
    public static function updating(callable $callback): void { static::registerModelEvent('updating', $callback); }
    public static function updated(callable $callback): void { static::registerModelEvent('updated', $callback); }
    public static function saving(callable $callback): void { static::registerModelEvent('saving', $callback); }
    public static function saved(callable $callback): void { static::registerModelEvent('saved', $callback); }
    public static function deleting(callable $callback): void { static::registerModelEvent('deleting', $callback); }
    public static function deleted(callable $callback): void { static::registerModelEvent('deleted', $callback); }

    // ----------------------------------------------------------------
    // Relationships
    // ----------------------------------------------------------------

    /**
     * Define a one-to-one relationship.
     */
    public function hasOne(string $related, ?string $foreignKey = null, ?string $localKey = null): HasOne
    {
        $instance = new $related;
        $foreignKey = $foreignKey ?: $this->getForeignKeyName();
        $localKey = $localKey ?: $this->primaryKey;

        return new HasOne($instance, $this, $foreignKey, $localKey);
    }

    /**
     * Define a one-to-many relationship.
     */
    public function hasMany(string $related, ?string $foreignKey = null, ?string $localKey = null): HasMany
    {
        $instance = new $related;
        $foreignKey = $foreignKey ?: $this->getForeignKeyName();
        $localKey = $localKey ?: $this->primaryKey;

        return new HasMany($instance, $this, $foreignKey, $localKey);
    }

    /**
     * Define an inverse one-to-one or one-to-many relationship.
     */
    public function belongsTo(string $related, ?string $foreignKey = null, ?string $ownerKey = null): BelongsTo
    {
        $instance = new $related;
        $foreignKey = $foreignKey ?: strtolower(basename(str_replace('\\', '/', $related))) . '_id';
        $ownerKey = $ownerKey ?: $instance->primaryKey;

        return new BelongsTo($instance, $this, $foreignKey, $ownerKey);
    }

    /**
     * Get the foreign key name for this model (e.g., "user_id").
     */
    protected function getForeignKeyName(): string
    {
        $class = basename(str_replace('\\', '/', static::class));
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $class)) . '_id';
    }

    /**
     * Get a loaded relationship.
     */
    public function getRelation(string $key): mixed
    {
        return $this->relations[$key] ?? null;
    }

    /**
     * Set a loaded relationship.
     */
    public function setRelation(string $key, mixed $value): self
    {
        $this->relations[$key] = $value;
        return $this;
    }

    // ----------------------------------------------------------------
    // Casting
    // ----------------------------------------------------------------

    protected function castAttribute(string $key, mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

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

    // ----------------------------------------------------------------
    // Serialization
    // ----------------------------------------------------------------

    /**
     * Convert the model to an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $attributes = $this->attributes;

        // Merge in relationship data
        foreach ($this->relations as $key => $value) {
            if (is_array($value)) {
                $attributes[$key] = array_map(fn($m) => $m instanceof self ? $m->toArray() : $m, $value);
            } elseif ($value instanceof self) {
                $attributes[$key] = $value->toArray();
            } else {
                $attributes[$key] = $value;
            }
        }

        // Append computed attributes
        foreach ($this->appends as $key) {
            $attributes[$key] = $this->getAttribute($key);
        }

        // Remove hidden attributes
        foreach ($this->hidden as $hidden) {
            unset($attributes[$hidden]);
        }

        return $attributes;
    }

    /**
     * Convert the model to JSON.
     */
    public function toJson(int $options = 0): string
    {
        return json_encode($this->toArray(), $options | JSON_THROW_ON_ERROR);
    }

    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }

    // ----------------------------------------------------------------
    // Magic
    // ----------------------------------------------------------------

    public function __get(string $key): mixed
    {
        return $this->getAttribute($key);
    }

    public function __set(string $key, mixed $value): void
    {
        $this->setAttribute($key, $value);
    }

    public function __isset(string $key): bool
    {
        return isset($this->attributes[$key]) || isset($this->relations[$key]);
    }

    public function __unset(string $key): void
    {
        unset($this->attributes[$key], $this->relations[$key]);
    }

    /**
     * Forward static calls to a new query instance.
     */
    public static function __callStatic(string $method, array $parameters): mixed
    {
        return (new static)->$method(...$parameters);
    }

    /**
     * Forward instance calls to the query builder.
     */
    public function __call(string $method, array $parameters): mixed
    {
        // Check for local scopes: scopeActive() → User::active()
        $scopeMethod = 'scope' . ucfirst($method);
        if (method_exists($this, $scopeMethod)) {
            $query = static::query();
            $this->{$scopeMethod}($query, ...$parameters);
            return $query;
        }

        return static::query()->$method(...$parameters);
    }

    // ----------------------------------------------------------------
    // ArrayAccess
    // ----------------------------------------------------------------

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->attributes[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->getAttribute($offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->setAttribute($offset, $value);
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->attributes[$offset]);
    }

    // ----------------------------------------------------------------
    // Helpers
    // ----------------------------------------------------------------

    /**
     * Convert snake_case to StudlyCase.
     */
    protected function studly(string $value): string
    {
        return str_replace(' ', '', ucwords(str_replace(['_', '-'], ' ', $value)));
    }
}
