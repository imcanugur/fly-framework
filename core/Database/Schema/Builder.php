<?php

declare(strict_types=1);

namespace Fly\Database\Schema;

use Fly\Support\Facades\DB;
use Closure;

/**
 * Executes schema operations.
 */
class Builder
{
    public static function create(string $table, Closure $callback): void
    {
        $blueprint = new Blueprint($table);
        $callback($blueprint);
        DB::statement($blueprint->build());
    }

    public static function dropIfExists(string $table): void
    {
        DB::statement("DROP TABLE IF EXISTS {$table}");
    }
}
