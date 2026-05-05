<?php

declare(strict_types=1);

namespace Fly\Support\Facades;

use Fly\Database\Schema\Builder;

/**
 * @method static void create(string $table, \Closure $callback)
 * @method static void dropIfExists(string $table)
 */
class Schema
{
    public static function create(string $table, \Closure $callback): void
    {
        Builder::create($table, $callback);
    }

    public static function dropIfExists(string $table): void
    {
        Builder::dropIfExists($table);
    }
}
