<?php

declare(strict_types=1);

namespace Fly\Database\Query;

use Fly\Database\Expression;

/**
 * SQL Grammar Compiler.
 *
 * Translates Query Builder state into raw SQL strings.
 * Supports SELECT, INSERT, UPDATE, DELETE with all clauses.
 */
class Grammar
{
    /**
     * Compile a select query into SQL.
     */
    public function compileSelect(Builder $query): string
    {
        $sql = [];

        $sql[] = $this->compileColumns($query);
        $sql[] = $this->compileFrom($query);

        if (!empty($query->joins)) {
            $sql[] = $this->compileJoins($query);
        }
        if (!empty($query->wheres)) {
            $sql[] = $this->compileWheres($query);
        }
        if (!empty($query->groups)) {
            $sql[] = $this->compileGroups($query);
        }
        if (!empty($query->havings)) {
            $sql[] = $this->compileHavings($query);
        }
        if (!empty($query->orders)) {
            $sql[] = $this->compileOrders($query);
        }
        if ($query->limitValue !== null) {
            $sql[] = $this->compileLimit($query);
        }
        if ($query->offsetValue !== null) {
            $sql[] = $this->compileOffset($query);
        }

        return implode(' ', array_filter($sql));
    }

    protected function compileColumns(Builder $query): string
    {
        $select = $query->distinct ? 'SELECT DISTINCT' : 'SELECT';
        if (empty($query->columns) || $query->columns === ['*']) {
            return "{$select} *";
        }
        $cols = array_map(fn($c) => $c instanceof Expression ? $c->getValue() : $c, $query->columns);
        return "{$select} " . implode(', ', $cols);
    }

    protected function compileFrom(Builder $query): string
    {
        return "FROM {$query->from}";
    }

    protected function compileJoins(Builder $query): string
    {
        $sql = [];
        foreach ($query->joins as $join) {
            $sql[] = strtoupper($join['type']) . " JOIN {$join['table']} ON {$join['first']} {$join['operator']} {$join['second']}";
        }
        return implode(' ', $sql);
    }

    public function compileWheres(Builder $query): string
    {
        if (empty($query->wheres)) {
            return '';
        }

        $clauses = [];
        foreach ($query->wheres as $i => $where) {
            $connector = $i === 0 ? '' : strtoupper($where['boolean']) . ' ';

            switch ($where['type']) {
                case 'basic':
                    $clauses[] = $connector . "{$where['column']} {$where['operator']} ?";
                    break;
                case 'in':
                    $placeholders = implode(', ', array_fill(0, count($where['values']), '?'));
                    $clauses[] = $connector . "{$where['column']} IN ({$placeholders})";
                    break;
                case 'notIn':
                    $placeholders = implode(', ', array_fill(0, count($where['values']), '?'));
                    $clauses[] = $connector . "{$where['column']} NOT IN ({$placeholders})";
                    break;
                case 'null':
                    $clauses[] = $connector . "{$where['column']} IS NULL";
                    break;
                case 'notNull':
                    $clauses[] = $connector . "{$where['column']} IS NOT NULL";
                    break;
                case 'between':
                    $clauses[] = $connector . "{$where['column']} BETWEEN ? AND ?";
                    break;
                case 'raw':
                    $clauses[] = $connector . $where['sql'];
                    break;
            }
        }

        return 'WHERE ' . implode(' ', $clauses);
    }

    protected function compileGroups(Builder $query): string
    {
        return 'GROUP BY ' . implode(', ', $query->groups);
    }

    protected function compileHavings(Builder $query): string
    {
        $clauses = [];
        foreach ($query->havings as $having) {
            $clauses[] = "{$having['column']} {$having['operator']} ?";
        }
        return 'HAVING ' . implode(' AND ', $clauses);
    }

    protected function compileOrders(Builder $query): string
    {
        $orders = array_map(function ($o) {
            $col = $o['column'] instanceof \Fly\Database\Expression ? $o['column']->getValue() : $o['column'];
            return trim("{$col} {$o['direction']}");
        }, $query->orders);
        return 'ORDER BY ' . implode(', ', $orders);
    }

    protected function compileLimit(Builder $query): string
    {
        return 'LIMIT ' . (int) $query->limitValue;
    }

    protected function compileOffset(Builder $query): string
    {
        return 'OFFSET ' . (int) $query->offsetValue;
    }

    /**
     * Compile an insert statement.
     */
    public function compileInsert(Builder $query, array $values): string
    {
        $table = $query->from;
        if (empty($values)) {
            return "INSERT INTO {$table} DEFAULT VALUES";
        }
        $columns = implode(', ', array_keys($values));
        $params = implode(', ', array_fill(0, count($values), '?'));
        return "INSERT INTO {$table} ({$columns}) VALUES ({$params})";
    }

    /**
     * Compile a batch insert statement.
     */
    public function compileInsertBatch(Builder $query, array $rows): string
    {
        $table = $query->from;
        $columns = implode(', ', array_keys($rows[0]));
        $rowParams = '(' . implode(', ', array_fill(0, count($rows[0]), '?')) . ')';
        $allParams = implode(', ', array_fill(0, count($rows), $rowParams));
        return "INSERT INTO {$table} ({$columns}) VALUES {$allParams}";
    }

    /**
     * Compile an update statement.
     */
    public function compileUpdate(Builder $query, array $values): string
    {
        $table = $query->from;
        $columns = implode(', ', array_map(fn($k) => "{$k} = ?", array_keys($values)));
        $sql = "UPDATE {$table} SET {$columns}";
        if (!empty($query->wheres)) {
            $sql .= ' ' . $this->compileWheres($query);
        }
        return $sql;
    }

    /**
     * Compile a delete statement.
     */
    public function compileDelete(Builder $query): string
    {
        $sql = "DELETE FROM {$query->from}";
        if (!empty($query->wheres)) {
            $sql .= ' ' . $this->compileWheres($query);
        }
        return $sql;
    }

    /**
     * Compile an aggregate query (COUNT, SUM, AVG, MIN, MAX).
     */
    public function compileAggregate(Builder $query, string $function, string $column): string
    {
        $aggr = strtoupper($function) . "({$column}) as aggregate";
        $sql = "SELECT {$aggr} FROM {$query->from}";
        if (!empty($query->wheres)) {
            $sql .= ' ' . $this->compileWheres($query);
        }
        return $sql;
    }

    /**
     * Compile a "truncate table" statement.
     */
    public function compileTruncate(Builder $query): string
    {
        return "DELETE FROM {$query->from}";
    }

    /**
     * Compile an "exists" sub-query.
     */
    public function compileExists(Builder $query): string
    {
        return "SELECT EXISTS({$this->compileSelect($query)}) as `exists`";
    }
}
