<?php

declare(strict_types=1);

namespace Fly\Database\Query;

use Fly\Database\Connection;
use Fly\Database\Expression;
use Closure;

/**
 * Fluent Query Builder.
 *
 * Provides a chainable API for constructing SQL queries.
 * Supports SELECT, INSERT, UPDATE, DELETE, JOINs, GROUP BY,
 * HAVING, ORDER BY, aggregates, sub-queries, and more.
 */
class Builder
{
    public string $from = '';
    public array $columns = [];
    public array $wheres = [];
    public array $orders = [];
    public array $groups = [];
    public array $havings = [];
    public array $joins = [];
    public ?int $limitValue = null;
    public ?int $offsetValue = null;
    public bool $distinct = false;

    /**
     * All bindings organized by type.
     *
     * @var array<string, array<int, mixed>>
     */
    public array $bindings = [
        'join'   => [],
        'where'  => [],
        'having' => [],
    ];

    public function __construct(
        public Connection $connection,
        public Grammar $grammar
    ) {}

    // ----------------------------------------------------------------
    // Clauses
    // ----------------------------------------------------------------

    public function from(string $table): self
    {
        $this->from = $table;
        return $this;
    }

    public function select(string|array ...$columns): self
    {
        $this->columns = [];
        foreach ($columns as $col) {
            if (is_array($col)) {
                $this->columns = array_merge($this->columns, $col);
            } else {
                $this->columns[] = $col;
            }
        }
        return $this;
    }

    public function addSelect(string|array ...$columns): self
    {
        foreach ($columns as $col) {
            if (is_array($col)) {
                $this->columns = array_merge($this->columns, $col);
            } else {
                $this->columns[] = $col;
            }
        }
        return $this;
    }

    public function distinct(): self
    {
        $this->distinct = true;
        return $this;
    }

    /**
     * Add a raw select expression.
     */
    public function selectRaw(string $expression): self
    {
        $this->columns[] = new Expression($expression);
        return $this;
    }

    /**
     * Apply the callback if the given value is truthy.
     */
    public function when(mixed $value, callable $callback, ?callable $default = null): self
    {
        if ($value) {
            return $callback($this, $value);
        } elseif ($default) {
            return $default($this, $value);
        }
        return $this;
    }

    /**
     * Apply the callback if the given value is falsy.
     */
    public function unless(mixed $value, callable $callback, ?callable $default = null): self
    {
        return $this->when(!$value, $callback, $default);
    }

    /**
     * Clone the query builder.
     */
    public function clone(): self
    {
        return clone $this;
    }

    // ----------------------------------------------------------------
    // WHERE Clauses
    // ----------------------------------------------------------------

    public function where(string|Closure $column, mixed $operator = null, mixed $value = null, string $boolean = 'and'): self
    {
        // Support: where('col', 'value') shorthand
        if ($value === null && $operator !== null && !is_callable($column)) {
            $value = $operator;
            $operator = '=';
        }

        $this->wheres[] = [
            'type'     => 'basic',
            'column'   => $column,
            'operator' => $operator,
            'value'    => $value,
            'boolean'  => $boolean,
        ];
        $this->bindings['where'][] = $value;

        return $this;
    }

    public function orWhere(string $column, mixed $operator = null, mixed $value = null): self
    {
        return $this->where($column, $operator, $value, 'or');
    }

    public function whereIn(string $column, array $values, string $boolean = 'and'): self
    {
        $this->wheres[] = [
            'type'    => 'in',
            'column'  => $column,
            'values'  => $values,
            'boolean' => $boolean,
        ];
        $this->bindings['where'] = array_merge($this->bindings['where'], $values);
        return $this;
    }

    public function whereNotIn(string $column, array $values, string $boolean = 'and'): self
    {
        $this->wheres[] = [
            'type'    => 'notIn',
            'column'  => $column,
            'values'  => $values,
            'boolean' => $boolean,
        ];
        $this->bindings['where'] = array_merge($this->bindings['where'], $values);
        return $this;
    }

    public function whereNull(string $column, string $boolean = 'and'): self
    {
        $this->wheres[] = ['type' => 'null', 'column' => $column, 'boolean' => $boolean];
        return $this;
    }

    public function whereNotNull(string $column, string $boolean = 'and'): self
    {
        $this->wheres[] = ['type' => 'notNull', 'column' => $column, 'boolean' => $boolean];
        return $this;
    }

    public function whereBetween(string $column, array $values, string $boolean = 'and'): self
    {
        $this->wheres[] = [
            'type'    => 'between',
            'column'  => $column,
            'values'  => $values,
            'boolean' => $boolean,
        ];
        $this->bindings['where'][] = $values[0];
        $this->bindings['where'][] = $values[1];
        return $this;
    }

    public function whereRaw(string $sql, array $bindings = [], string $boolean = 'and'): self
    {
        $this->wheres[] = ['type' => 'raw', 'sql' => $sql, 'boolean' => $boolean];
        $this->bindings['where'] = array_merge($this->bindings['where'], $bindings);
        return $this;
    }

    // ----------------------------------------------------------------
    // JOINs
    // ----------------------------------------------------------------

    public function join(string $table, string $first, string $operator, string $second, string $type = 'inner'): self
    {
        $this->joins[] = compact('table', 'first', 'operator', 'second', 'type');
        return $this;
    }

    public function leftJoin(string $table, string $first, string $operator, string $second): self
    {
        return $this->join($table, $first, $operator, $second, 'left');
    }

    public function rightJoin(string $table, string $first, string $operator, string $second): self
    {
        return $this->join($table, $first, $operator, $second, 'right');
    }

    // ----------------------------------------------------------------
    // ORDER BY, GROUP BY, HAVING
    // ----------------------------------------------------------------

    public function orderBy(string $column, string $direction = 'asc'): self
    {
        $this->orders[] = ['column' => $column, 'direction' => strtoupper($direction)];
        return $this;
    }

    public function orderByDesc(string $column): self
    {
        return $this->orderBy($column, 'desc');
    }

    public function latest(string $column = 'created_at'): self
    {
        return $this->orderBy($column, 'desc');
    }

    public function oldest(string $column = 'created_at'): self
    {
        return $this->orderBy($column, 'asc');
    }

    public function groupBy(string ...$groups): self
    {
        $this->groups = array_merge($this->groups, $groups);
        return $this;
    }

    public function having(string $column, string $operator, mixed $value): self
    {
        $this->havings[] = compact('column', 'operator', 'value');
        $this->bindings['having'][] = $value;
        return $this;
    }

    /**
     * Add a raw order by clause.
     */
    public function orderByRaw(string $sql): self
    {
        $this->orders[] = ['column' => new Expression($sql), 'direction' => ''];
        return $this;
    }

    /**
     * Add a "where column = column" clause.
     */
    public function whereColumn(string $first, string $operator, ?string $second = null, string $boolean = 'and'): self
    {
        if ($second === null) {
            $second = $operator;
            $operator = '=';
        }
        $this->wheres[] = [
            'type'     => 'raw',
            'sql'      => "{$first} {$operator} {$second}",
            'boolean'  => $boolean,
        ];
        return $this;
    }

    // ----------------------------------------------------------------
    // LIMIT, OFFSET
    // ----------------------------------------------------------------

    public function limit(int $value): self
    {
        $this->limitValue = max(0, $value);
        return $this;
    }

    public function take(int $value): self
    {
        return $this->limit($value);
    }

    public function offset(int $value): self
    {
        $this->offsetValue = max(0, $value);
        return $this;
    }

    public function skip(int $value): self
    {
        return $this->offset($value);
    }

    /**
     * Paginate results.
     */
    public function forPage(int $page, int $perPage = 15): self
    {
        return $this->offset(($page - 1) * $perPage)->limit($perPage);
    }

    // ----------------------------------------------------------------
    // Execution: SELECT
    // ----------------------------------------------------------------

    /**
     * Execute the query as a "select" statement.
     *
     * @return array<int, object>
     */
    public function get(): array
    {
        $sql = $this->grammar->compileSelect($this);
        return $this->connection->select($sql, $this->getBindings());
    }

    /**
     * Execute the query and get the first result.
     */
    public function first(): ?object
    {
        return $this->limit(1)->get()[0] ?? null;
    }

    /**
     * Get a single column's value from the first result.
     */
    public function value(string $column): mixed
    {
        $result = $this->select($column)->first();
        return $result ? $result->{$column} : null;
    }

    /**
     * Get an array of values for a single column.
     *
     * @return array<int, mixed>
     */
    public function pluck(string $column, ?string $key = null): array
    {
        $results = $this->select(array_filter([$column, $key]))->get();

        if ($key !== null) {
            $out = [];
            foreach ($results as $row) {
                $out[$row->{$key}] = $row->{$column};
            }
            return $out;
        }

        return array_map(fn($row) => $row->{$column}, $results);
    }

    /**
     * Determine if any rows exist for the current query.
     */
    public function exists(): bool
    {
        $sql = $this->grammar->compileExists($this);
        $result = $this->connection->selectOne($sql, $this->getBindings());
        return (bool) ($result->exists ?? false);
    }

    /**
     * Chunk the results of the query.
     */
    public function chunk(int $count, callable $callback): bool
    {
        $page = 1;
        do {
            $results = $this->forPage($page, $count)->get();
            $countResults = count($results);

            if ($countResults === 0) {
                break;
            }

            if ($callback($results, $page) === false) {
                return false;
            }

            $page++;
        } while ($countResults === $count);

        return true;
    }

    // ----------------------------------------------------------------
    // Aggregates
    // ----------------------------------------------------------------

    public function count(string $column = '*'): int
    {
        return (int) $this->aggregate('count', $column);
    }

    public function sum(string $column): float
    {
        return (float) $this->aggregate('sum', $column);
    }

    public function avg(string $column): float
    {
        return (float) $this->aggregate('avg', $column);
    }

    public function min(string $column): mixed
    {
        return $this->aggregate('min', $column);
    }

    public function max(string $column): mixed
    {
        return $this->aggregate('max', $column);
    }

    protected function aggregate(string $function, string $column): mixed
    {
        $sql = $this->grammar->compileAggregate($this, $function, $column);
        $result = $this->connection->selectOne($sql, $this->getBindings());
        return $result?->aggregate;
    }

    // ----------------------------------------------------------------
    // Execution: INSERT / UPDATE / DELETE
    // ----------------------------------------------------------------

    /**
     * Insert a new record into the database.
     */
    public function insert(array $values): bool
    {
        $sql = $this->grammar->compileInsert($this, $values);
        return $this->connection->insert($sql, array_values($values));
    }

    /**
     * Insert multiple records.
     */
    public function insertBatch(array $rows): bool
    {
        if (empty($rows)) {
            return true;
        }
        $sql = $this->grammar->compileInsertBatch($this, $rows);
        $bindings = [];
        foreach ($rows as $row) {
            $bindings = array_merge($bindings, array_values($row));
        }
        return $this->connection->insert($sql, $bindings);
    }

    /**
     * Insert a new record and get the auto-generated ID.
     */
    public function insertGetId(array $values): int|string
    {
        $sql = $this->grammar->compileInsert($this, $values);
        return $this->connection->insertGetId($sql, array_values($values));
    }

    /**
     * Update records in the database.
     */
    public function update(array $values): int
    {
        $sql = $this->grammar->compileUpdate($this, $values);
        $bindings = array_merge(array_values($values), $this->bindings['where']);
        return $this->connection->update($sql, $bindings);
    }

    /**
     * Increment a column's value.
     */
    public function increment(string $column, int $amount = 1, array $extra = []): int
    {
        $wrapped = array_merge([$column => new Expression("{$column} + {$amount}")], $extra);
        // For expression values, compile manually
        $sets = [];
        $bindings = [];
        foreach ($wrapped as $key => $val) {
            if ($val instanceof Expression) {
                $sets[] = "{$key} = {$val->getValue()}";
            } else {
                $sets[] = "{$key} = ?";
                $bindings[] = $val;
            }
        }
        $sql = "UPDATE {$this->from} SET " . implode(', ', $sets);
        if (!empty($this->wheres)) {
            $sql .= ' ' . $this->grammar->compileWheres($this);
        }
        $bindings = array_merge($bindings, $this->bindings['where']);
        return $this->connection->update($sql, $bindings);
    }

    /**
     * Decrement a column's value.
     */
    public function decrement(string $column, int $amount = 1, array $extra = []): int
    {
        return $this->increment($column, -$amount, $extra);
    }

    /**
     * Delete records from the database.
     */
    public function delete(): int
    {
        $sql = $this->grammar->compileDelete($this);
        return $this->connection->delete($sql, $this->bindings['where']);
    }

    /**
     * Run a truncate statement on the table.
     */
    public function truncate(): void
    {
        $sql = $this->grammar->compileTruncate($this);
        $this->connection->statement($sql);
    }

    // ----------------------------------------------------------------
    // Helpers
    // ----------------------------------------------------------------

    /**
     * Get the flattened array of all bindings.
     *
     * @return array<int, mixed>
     */
    public function getBindings(): array
    {
        $all = [];
        foreach (['join', 'where', 'having'] as $type) {
            $all = array_merge($all, $this->bindings[$type]);
        }
        return $all;
    }

    /**
     * Get the SQL representation of the query.
     */
    public function toSql(): string
    {
        return $this->grammar->compileSelect($this);
    }

    /**
     * Dump the query to the standard output (for debugging).
     */
    public function dd(): never
    {
        echo $this->toSql() . PHP_EOL;
        print_r($this->getBindings());
        exit(1);
    }
}
