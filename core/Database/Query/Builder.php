<?php

declare(strict_types=1);

namespace Fly\Database\Query;

use Fly\Database\Connection;

/**
 * Fluent Query Builder.
 */
class Builder
{
    public string $from = '';
    public array $columns = [];
    public array $wheres = [];
    public ?int $limit = null;

    public array $bindings = [
        'where' => []
    ];

    public function __construct(
        public Connection $connection,
        public Grammar $grammar
    ) {}

    /**
     * Set the table which the query is targeting.
     */
    public function from(string $table): self
    {
        $this->from = $table;
        return $this;
    }

    /**
     * Set the columns to be selected.
     */
    public function select(array $columns = ['*']): self
    {
        $this->columns = $columns;
        return $this;
    }

    /**
     * Add a basic where clause to the query.
     */
    public function where(string $column, string $operator, mixed $value = null, string $boolean = 'and'): self
    {
        // If value is null, assume operator is '=' and the passed operator is the value
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        $this->wheres[] = compact('column', 'operator', 'value', 'boolean');
        $this->bindings['where'][] = $value;

        return $this;
    }

    /**
     * Add an "or where" clause to the query.
     */
    public function orWhere(string $column, string $operator, mixed $value = null): self
    {
        return $this->where($column, $operator, $value, 'or');
    }

    /**
     * Set the "limit" value of the query.
     */
    public function limit(int $value): self
    {
        $this->limit = $value;
        return $this;
    }

    /**
     * Execute the query as a "select" statement.
     */
    public function get(): array
    {
        $sql = $this->grammar->compileSelect($this);
        return $this->connection->select($sql, $this->bindings['where']);
    }

    /**
     * Execute the query and get the first result.
     */
    public function first(): ?object
    {
        $result = $this->limit(1)->get();
        return $result[0] ?? null;
    }

    /**
     * Insert a new record into the database.
     */
    public function insert(array $values): bool
    {
        $sql = $this->grammar->compileInsert($this, $values);
        return $this->connection->insert($sql, array_values($values));
    }

    /**
     * Update a record in the database.
     */
    public function update(array $values): int
    {
        $sql = $this->grammar->compileUpdate($this, $values);
        
        $bindings = array_merge(array_values($values), $this->bindings['where']);
        
        return $this->connection->update($sql, $bindings);
    }

    /**
     * Delete a record from the database.
     */
    public function delete(): int
    {
        $sql = $this->grammar->compileDelete($this);
        return $this->connection->delete($sql, $this->bindings['where']);
    }
}
