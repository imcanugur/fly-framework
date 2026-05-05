<?php

declare(strict_types=1);

/**
 * Fly Framework - Bootstrap Loader
 *
 * Creates the Application instance and registers core services.
 * This file is the single source of truth for application construction.
 */

use Fly\Application\Application;
use Fly\Routing\Router;

// Determine the project base path
$basePath = dirname(__DIR__);

// Create the application
$app = new Application($basePath);

// Register core singletons
$app->singleton(Router::class, fn() => new Router());

// Register service providers
$app->register(\App\Providers\AppServiceProvider::class);

return $app;
