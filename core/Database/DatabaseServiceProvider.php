<?php

declare(strict_types=1);

namespace Fly\Database;

use Fly\Support\ServiceProvider;

/**
 * Registers the database connection manager.
 */
class DatabaseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(DatabaseManager::class, function ($app) {
            return new DatabaseManager($app);
        });

        $this->app->singleton('db', function ($app) {
            return $app->make(DatabaseManager::class);
        });
    }

    public function boot(): void
    {
        //
    }
}
