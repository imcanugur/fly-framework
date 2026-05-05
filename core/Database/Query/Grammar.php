<?php

declare(strict_types=1);

namespace Fly\Database\Query;

/**
 * Base SQL Grammar Compiler.
 *
 * Compiles fluent Builder logic into plain SQL queries.
 */
class Grammar
{
    /**
     * Compile a select query into SQL.
     */
    public function compileSelect(Builder $query): string
    {
        $sql = "SELECT " . $this->compileColumns($query->columns) . " FROM " . $query->from;

        if (!empty($query->wheres)) {
            $sql .= " WHERE " . $this->compileWheres($query->wheres);
        }

        if ($query->limit !== null) {
            $sql .= " LIMIT " . (int) $query->limit;
        }

        return $sql;
    }

    protected function compileColumns(array $columns): string
    {
        return empty($columns) ? '*' : implode(', ', $columns);
    }

    protected function compileWheres(array $wheres): string
    {
        $sql = [];

        foreach ($wheres as $i => $where) {
            $connector = $i === 0 ? '' : ($where['boolean'] . ' ');
            $sql[] = $connector . $where['column'] . ' ' . $where['operator'] . ' ?';
        }

        return implode(' ', $sql);
    }

    /**
     * Compile an insert statement into SQL.
     */
    public function compileInsert(Builder $query, array $values): string
    {
        $table = $query->from;

        if (empty($values)) {
            return "INSERT INTO {$table} DEFAULT VALUES";
        }

        $columns = implode(', ', array_keys($values));
        $parameters = implode(', ', array_fill(0, count($values), '?'));

        return "INSERT INTO {$table} ({$columns}) VALUES ({$parameters})";
    }

    /**
     * Compile an update statement into SQL.
     */
    public function compileUpdate(Builder $query, array $values): string
    {
        $table = $query->from;
        
        $columns = implode(' = ?, ', array_keys($values)) . ' = ?';
        
        $sql = "UPDATE {$table} SET {$columns}";

        if (!empty($query->wheres)) {
            $sql .= " WHERE " . $this->compileWheres($query->wheres);
        }

        return $sql;
    }

    /**
     * Compile a delete statement into SQL.
     */
    public function compileDelete(Builder $query): string
    {
        $table = $query->from;
        $sql = "DELETE FROM {$table}";

        if (!empty($query->wheres)) {
            $sql .= " WHERE " . $this->compileWheres($query->wheres);
        }

        return $sql;
    }
}
