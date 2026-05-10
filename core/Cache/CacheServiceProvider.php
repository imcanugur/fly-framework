<?php

declare(strict_types=1);

namespace Fly\Cache;

use Fly\Support\ServiceProvider;

class CacheServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->singleton(CacheManager::class, function ($app) {
            return new CacheManager($app);
        });

        $this->app->alias(CacheManager::class, 'cache');
    }

    /**
     * Boot the service provider.
     */
    public function boot(): void
    {
        //
    }
}
