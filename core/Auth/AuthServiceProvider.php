<?php

declare(strict_types=1);

namespace Fly\Auth;

use Fly\Support\ServiceProvider;
use Fly\Hashing\HashManager;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->singleton(AuthManager::class, function ($app) {
            return new AuthManager($app);
        });

        $this->app->alias(AuthManager::class, 'auth');

        $this->app->singleton(\Fly\Auth\Access\Gate::class, function ($app) {
            return new \Fly\Auth\Access\Gate($app, function () use ($app) {
                return $app->make('auth')->user();
            });
        });

        $this->app->alias(\Fly\Auth\Access\Gate::class, 'gate');
    }

    /**
     * Boot the service provider.
     */
    public function boot(): void
    {
        //
    }
}
