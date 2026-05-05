<?php

declare(strict_types=1);

namespace App\Providers;

use Fly\Support\ServiceProvider;

/**
 * Main application service provider.
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register your application-specific services here
        // $this->app->singleton(MyService::class, fn () => new MyService());
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Boot logic like registering view components, macros, etc.
    }
}
