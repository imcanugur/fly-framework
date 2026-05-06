<?php

declare(strict_types=1);

namespace Fly\Database\ORM;

use Fly\Database\Query\Builder as QueryBuilder;
use Fly\Support\Collection;

/**
 * ORM Query Builder.
 *
 * Wraps the base Query Builder to hydrate results
 * into Model instances and support eager loading.
 */
class Builder
{
    public function __construct(
        protected QueryBuilder $query,
        protected Model $model
    ) {}

    /**
     * Execute the query and hydrate results into models.
     *
     * @return Collection<int, Model>
     */
    public function get(): Collection
    {
        $records = $this->query->get();
        $models = array_map(fn($r) => $this->model::hydrate((array) $r), $records);
        
        if (count($models) > 0) {
            $models = $this->eagerLoadRelations($models);
        }
        
        return new Collection($models);
    }

    /**
     * Execute the query and get the first result as a model.
     */
    public function first(): ?Model
    {
        $record = $this->query->first();
        if (!$record) {
            return null;
        }
        $model = $this->model::hydrate((array) $record);
        
        if (!empty($this->eagerLoads)) {
            $model = $this->eagerLoadRelations([$model])[0];
        }
        
        return $model;
    }

    /**
     * Find a model by its primary key.
     */
    public function find(int|string $id): ?Model
    {
        return $this->where($this->model->getKeyName(), $id)->first();
    }

    /**
     * Paginate results.
     *
     * @return array{data: array, current_page: int, per_page: int, total: int, last_page: int}
     */
    public function paginate(int $perPage = 15, int $page = 1): array
    {
        $total = $this->query->count();
        $lastPage = (int) ceil($total / $perPage);

        $results = (clone $this->query)->forPage($page, $perPage)->get();
        $data = array_map(fn($r) => $this->model::hydrate((array) $r), $results);
        
        if (count($data) > 0) {
            $data = $this->eagerLoadRelations($data);
        }

        return [
            'data'         => $data,
            'current_page' => $page,
            'per_page'     => $perPage,
            'total'        => $total,
            'last_page'    => $lastPage,
        ];
    }

    /**
     * Eager load relationships.
     *
     * @param  string ...$relations
     */
    public function with(string ...$relations): self
    {
        // Store for post-processing after get()
        $this->eagerLoads = $relations;
        return $this;
    }

    /** @var array<int, string> */
    protected array $eagerLoads = [];

    /**
     * Eager load the relationships for the models.
     *
     * @param array<int, Model> $models
     * @return array<int, Model>
     */
    protected function eagerLoadRelations(array $models): array
    {
        foreach ($this->eagerLoads as $relation) {
            if (empty($models)) {
                continue;
            }

            // Get relationship instance from the first model
            $relationInstance = $models[0]->{$relation}();
            
            $models = $relationInstance->match(
                $models,
                $relationInstance->getEager($models, $relation),
                $relation
            );
        }

        return $models;
    }

    /**
     * Get the underlying query builder.
     */
    public function getQuery(): QueryBuilder
    {
        return $this->query;
    }

    /**
     * Get the model instance being queried.
     */
    public function getModel(): Model
    {
        return $this->model;
    }

    /**
     * Proxy all other calls to the underlying query builder.
     * Returns $this when the query builder returns itself (chaining).
     */
    public function __call(string $method, array $parameters): mixed
    {
        $result = $this->query->$method(...$parameters);

        // If the query builder returned itself, return $this for ORM chaining
        if ($result === $this->query) {
            return $this;
        }

        return $result;
    }
}
