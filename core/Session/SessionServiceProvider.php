<?php

declare(strict_types=1);

namespace Fly\Session;

use Fly\Support\ServiceProvider;

class SessionServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->singleton(SessionManager::class, function ($app) {
            return new SessionManager($app);
        });

        $this->app->alias(SessionManager::class, 'session');
    }

    /**
     * Boot the service provider.
     */
    public function boot(): void
    {
        //
    }
}
