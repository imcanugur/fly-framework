<?php

declare(strict_types=1);

namespace Fly\Database\ORM\Relations;

use Fly\Database\ORM\Model;
use Fly\Support\Facades\DB;
use Fly\Support\Collection;

/**
 * HasOne Relationship.
 *
 * Example: User hasOne Phone
 * phone table has a user_id column pointing back to users.id
 */
class HasOne
{
    public function __construct(
        protected Model $related,
        protected Model $parent,
        protected string $foreignKey,
        protected string $localKey,
    ) {}

    /**
     * Get the result of the relationship.
     */
    public function getResults(): ?Model
    {
        $parentKeyValue = $this->parent->getAttribute($this->localKey);

        if ($parentKeyValue === null) {
            return null;
        }

        $record = DB::table($this->related->getTable())
            ->where($this->foreignKey, $parentKeyValue)
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
            $key = $model->getAttribute($this->localKey);
            if ($key !== null && !in_array($key, $keys, true)) {
                $keys[] = $key;
            }
        }

        if (empty($keys)) {
            return new Collection();
        }

        $records = DB::table($this->related->getTable())
            ->whereIn($this->foreignKey, $keys)
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
            $key = $result->getAttribute($this->foreignKey);
            $dictionary[$key] = $result;
        }

        foreach ($models as $model) {
            $key = $model->getAttribute($this->localKey);
            $value = $dictionary[$key] ?? null;
            $model->setRelation($relation, $value);
        }

        return $models;
    }
}
