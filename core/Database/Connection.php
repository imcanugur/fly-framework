<?php

declare(strict_types=1);

namespace Fly\Database;

use PDO;
use PDOStatement;
use Fly\Database\Query\Builder;
use Fly\Database\Query\Grammar;

/**
 * Database Connection.
 *
 * Wraps a PDO instance and provides a clean API for executing
 * queries, managing transactions, and query logging.
 */
class Connection
{
    protected PDO $pdo;
    protected Grammar $grammar;
    protected string $tablePrefix;

    /**
     * Query log for debugging.
     *
     * @var array<int, array{query: string, bindings: array, time: float}>
     */
    protected array $queryLog = [];
    protected bool $loggingQueries = false;

    public function __construct(PDO $pdo, string $tablePrefix = '')
    {
        $this->pdo = $pdo;
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
        $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        $this->grammar = new Grammar();
        $this->tablePrefix = $tablePrefix;
    }

    // ----------------------------------------------------------------
    // PDO Access
    // ----------------------------------------------------------------

    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    public function getGrammar(): Grammar
    {
        return $this->grammar;
    }

    public function getTablePrefix(): string
    {
        return $this->tablePrefix;
    }

    public function getDriverName(): string
    {
        return $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    }

    // ----------------------------------------------------------------
    // Query Builder Entry
    // ----------------------------------------------------------------

    /**
     * Begin a fluent query against a database table.
     */
    public function table(string $table): Builder
    {
        return (new Builder($this, $this->grammar))->from($table);
    }

    /**
     * Get a new raw expression.
     */
    public function raw(string $value): Expression
    {
        return new Expression($value);
    }

    // ----------------------------------------------------------------
    // Statement Execution
    // ----------------------------------------------------------------

    /**
     * Run a select statement against the database.
     *
     * @return array<int, object>
     */
    public function select(string $query, array $bindings = []): array
    {
        return $this->run($query, $bindings, function (string $query, array $bindings) {
            $statement = $this->pdo->prepare($query);
            $statement->execute($this->prepareBindings($bindings));
            return $statement->fetchAll(PDO::FETCH_OBJ);
        });
    }

    /**
     * Run a select statement and return a single column.
     */
    public function selectOne(string $query, array $bindings = []): ?object
    {
        $results = $this->select($query, $bindings);
        return $results[0] ?? null;
    }

    /**
     * Run an insert statement against the database.
     */
    public function insert(string $query, array $bindings = []): bool
    {
        return $this->statement($query, $bindings);
    }

    /**
     * Run an insert statement and return the auto-generated ID.
     */
    public function insertGetId(string $query, array $bindings = []): int|string
    {
        $this->statement($query, $bindings);
        $id = $this->pdo->lastInsertId();
        return is_numeric($id) ? (int) $id : $id;
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
        return $this->run($query, $bindings, function (string $query, array $bindings) {
            return $this->pdo->prepare($query)->execute($this->prepareBindings($bindings));
        });
    }

    /**
     * Run an SQL statement and get the number of rows affected.
     */
    public function affectingStatement(string $query, array $bindings = []): int
    {
        return $this->run($query, $bindings, function (string $query, array $bindings) {
            $statement = $this->pdo->prepare($query);
            $statement->execute($this->prepareBindings($bindings));
            return $statement->rowCount();
        });
    }

    /**
     * Run a raw, unprepared query against the PDO connection.
     */
    public function unprepared(string $query): bool
    {
        return $this->run($query, [], function (string $query) {
            return $this->pdo->exec($query) !== false;
        });
    }

    // ----------------------------------------------------------------
    // Transactions
    // ----------------------------------------------------------------

    /**
     * Execute a Closure within a transaction.
     */
    public function transaction(\Closure $callback): mixed
    {
        $this->beginTransaction();

        try {
            $result = $callback($this);
            $this->commit();
            return $result;
        } catch (\Throwable $e) {
            $this->rollBack();
            throw $e;
        }
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

    public function inTransaction(): bool
    {
        return $this->pdo->inTransaction();
    }

    // ----------------------------------------------------------------
    // Query Logging
    // ----------------------------------------------------------------

    /**
     * Enable the query log.
     */
    public function enableQueryLog(): void
    {
        $this->loggingQueries = true;
    }

    /**
     * Disable the query log.
     */
    public function disableQueryLog(): void
    {
        $this->loggingQueries = false;
    }

    /**
     * Get the query log.
     *
     * @return array<int, array{query: string, bindings: array, time: float}>
     */
    public function getQueryLog(): array
    {
        return $this->queryLog;
    }

    /**
     * Clear the query log.
     */
    public function flushQueryLog(): void
    {
        $this->queryLog = [];
    }

    // ----------------------------------------------------------------
    // Internal
    // ----------------------------------------------------------------

    /**
     * Run a SQL query with timing and logging.
     */
    protected function run(string $query, array $bindings, \Closure $callback): mixed
    {
        $start = microtime(true);

        $result = $callback($query, $bindings);

        $time = round((microtime(true) - $start) * 1000, 2);

        if ($this->loggingQueries) {
            $this->queryLog[] = compact('query', 'bindings', 'time');
        }

        return $result;
    }

    /**
     * Prepare the query bindings for execution.
     *
     * @param  array<int, mixed>  $bindings
     * @return array<int, mixed>
     */
    protected function prepareBindings(array $bindings): array
    {
        return array_map(function (mixed $binding) {
            if ($binding instanceof \DateTimeInterface) {
                return $binding->format('Y-m-d H:i:s');
            }
            if (is_bool($binding)) {
                return (int) $binding;
            }
            return $binding;
        }, $bindings);
    }
}
