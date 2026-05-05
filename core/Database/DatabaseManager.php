<?php

declare(strict_types=1);

namespace Fly\Database;

use PDO;
use Exception;
use Fly\Application\Application;

/**
 * Manages all active database connections.
 */
class DatabaseManager
{
    /**
     * The active connection instances.
     *
     * @var array<string, Connection>
     */
    protected array $connections = [];

    public function __construct(protected readonly Application $app) {}

    /**
     * Get a database connection instance.
     */
    public function connection(?string $name = null): Connection
    {
        $name = $name ?: $this->getDefaultConnection();

        if (!isset($this->connections[$name])) {
            $this->connections[$name] = $this->makeConnection($name);
        }

        return $this->connections[$name];
    }

    /**
     * Make the database connection instance.
     */
    protected function makeConnection(string $name): Connection
    {
        $config = $this->app->make('config')->get("database.connections.{$name}");

        if (!$config) {
            throw new Exception("Database connection [{$name}] not configured.");
        }

        return new Connection($this->createPdo($config));
    }

    /**
     * Create a new PDO instance.
     */
    protected function createPdo(array $config): PDO
    {
        $driver = $config['driver'] ?? 'mysql';

        if ($driver === 'sqlite') {
            // Support memory databases or physical files
            $database = $config['database'];
            if ($database !== ':memory:') {
                // Ensure directory exists
                $dir = dirname($database);
                if (!is_dir($dir)) {
                    mkdir($dir, 0777, true);
                }
            }
            $pdo = new PDO("sqlite:{$database}");
        } else {
            $dsn = "{$driver}:host={$config['host']};port={$config['port']};dbname={$config['database']}";
            $pdo = new PDO($dsn, $config['username'] ?? 'root', $config['password'] ?? '', [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        }

        return $pdo;
    }

    /**
     * Get the default connection name.
     */
    public function getDefaultConnection(): string
    {
        return $this->app->make('config')->get('database.default', 'mysql');
    }

    /**
     * Begin a fluent query against a database table.
     */
    public function table(string $table): Query\Builder
    {
        return $this->connection()->table($table);
    }

    /**
     * Dynamically pass methods to the default connection.
     */
    public function __call(string $method, array $parameters): mixed
    {
        return $this->connection()->$method(...$parameters);
    }
}
