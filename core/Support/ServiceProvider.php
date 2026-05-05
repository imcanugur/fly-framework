<?php

declare(strict_types=1);

namespace Fly\Support;

use Fly\Application\Application;

/**
 * Base Service Provider.
 *
 * Service providers are the central place to configure and bind
 * components into the application's service container.
 */
abstract class ServiceProvider
{
    /**
     * Create a new service provider instance.
     */
    public function __construct(
        protected readonly Application $app
    ) {}

    /**
     * Register any application services.
     *
     * This method is called first, allowing you to bind things into the container.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * This method is called after all other service providers have been registered.
     */
    public function boot(): void
    {
        //
    }
}
