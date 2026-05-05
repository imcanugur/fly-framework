<?php

declare(strict_types=1);

namespace Fly\Database;

use PDO;
use Fly\Database\Query\Builder;
use Fly\Database\Query\Grammar;

/**
 * Wraps the PDO instance and manages SQL statement execution.
 */
class Connection
{
    protected PDO $pdo;
    protected Grammar $grammar;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->grammar = new Grammar();
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    /**
     * Begin a fluent query against a database table.
     */
    public function table(string $table): Builder
    {
        return clone (new Builder($this, $this->grammar))->from($table);
    }

    /**
     * Run a select statement against the database.
     */
    public function select(string $query, array $bindings = []): array
    {
        $statement = $this->pdo->prepare($query);
        $statement->execute($bindings);

        return $statement->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Run an insert statement against the database.
     */
    public function insert(string $query, array $bindings = []): bool
    {
        return $this->statement($query, $bindings);
    }

    /**
     * Run an update statement against the database.
     */
    public function update(string $query, array $bindings = []): int
    {
        return $this->affectingStatement($query, $bindings);
    }

    /**
     * Run a delete statement against the database.
     */
    public function delete(string $query, array $bindings = []): int
    {
        return $this->affectingStatement($query, $bindings);
    }

    /**
     * Execute an SQL statement and return the boolean result.
     */
    public function statement(string $query, array $bindings = []): bool
    {
        return $this->pdo->prepare($query)->execute($bindings);
    }

    /**
     * Run an SQL statement and get the number of rows affected.
     */
    public function affectingStatement(string $query, array $bindings = []): int
    {
        $statement = $this->pdo->prepare($query);
        $statement->execute($bindings);

        return $statement->rowCount();
    }

    public function beginTransaction(): void
    {
        $this->pdo->beginTransaction();
    }

    public function commit(): void
    {
        $this->pdo->commit();
    }

    public function rollBack(): void
    {
        $this->pdo->rollBack();
    }
}
