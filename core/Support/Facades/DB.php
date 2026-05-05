<?php

declare(strict_types=1);

namespace Fly\Support\Facades;

/**
 * @method static \Fly\Database\Connection connection(string $name = null)
 * @method static \Fly\Database\Query\Builder table(string $table)
 * @method static array select(string $query, array $bindings = [])
 * @method static bool insert(string $query, array $bindings = [])
 * @method static int update(string $query, array $bindings = [])
 * @method static int delete(string $query, array $bindings = [])
 * @method static bool statement(string $query, array $bindings = [])
 * @method static void beginTransaction()
 * @method static void commit()
 * @method static void rollBack()
 *
 * @see \Fly\Database\DatabaseManager
 * @see \Fly\Database\Connection
 */
class DB extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'db';
    }
}
