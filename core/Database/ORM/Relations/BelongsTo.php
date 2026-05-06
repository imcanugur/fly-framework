<?php

declare(strict_types=1);

namespace Fly\Database\ORM\Relations;

use Fly\Database\ORM\Model;
use Fly\Support\Facades\DB;
use Fly\Support\Collection;

/**
 * BelongsTo Relationship (Inverse of HasOne / HasMany).
 *
 * Example: Post belongsTo User
 * posts table has a user_id column pointing to users.id
 */
class BelongsTo
{
    public function __construct(
        protected Model $related,
        protected Model $child,
        protected string $foreignKey,
        protected string $ownerKey,
    ) {}

    /**
     * Get the result of the relationship.
     */
    public function getResults(): ?Model
    {
        $foreignKeyValue = $this->child->getAttribute($this->foreignKey);

        if ($foreignKeyValue === null) {
            return null;
        }

        $record = DB::table($this->related->getTable())
            ->where($this->ownerKey, $foreignKeyValue)
            ->first();

        if (!$record) {
            return null;
        }

        return $this->related::hydrate((array) $record);
    }

    /**
     * Get the eager loaded results for the models.
     */
    public function getEager(array $models, string $relation): Collection
    {
        $keys = [];
        foreach ($models as $model) {
            $key = $model->getAttribute($this->foreignKey);
            if ($key !== null && !in_array($key, $keys, true)) {
                $keys[] = $key;
            }
        }

        if (empty($keys)) {
            return new Collection();
        }

        $records = DB::table($this->related->getTable())
            ->whereIn($this->ownerKey, $keys)
            ->get();

        $relatedModels = array_map(
            fn($r) => $this->related::hydrate((array) $r),
            $records
        );

        return new Collection($relatedModels);
    }

    /**
     * Match the eagerly loaded results to their parents.
     */
    public function match(array $models, Collection $results, string $relation): array
    {
        $dictionary = [];

        foreach ($results->all() as $result) {
            $key = $result->getAttribute($this->ownerKey);
            $dictionary[$key] = $result;
        }

        foreach ($models as $model) {
            $key = $model->getAttribute($this->foreignKey);
            $value = $dictionary[$key] ?? null;
            $model->setRelation($relation, $value);
        }

        return $models;
    }
}
