<?php

declare(strict_types=1);

namespace Fly\Routing;

use Closure;
use Fly\Container\Container;

/**
 * Static route registration facade.
 *
 * Provides the expressive Route::get() / Route::post() API
 * by delegating to the underlying Router instance from the container.
 *
 * Usage:
 *   Route::get('/', fn () => 'Hello');
 *   Route::get('/users/{id}', [UserController::class, 'show']);
 */
class Route
{
    /**
     * Register a GET route.
     */
    public static function get(string $uri, Closure|array $action): void
    {
        static::router()->get($uri, $action);
    }

    /**
     * Register a POST route.
     */
    public static function post(string $uri, Closure|array $action): void
    {
        static::router()->post($uri, $action);
    }

    /**
     * Register a PUT route.
     */
    public static function put(string $uri, Closure|array $action): void
    {
        static::router()->put($uri, $action);
    }

    /**
     * Register a PATCH route.
     */
    public static function patch(string $uri, Closure|array $action): void
    {
        static::router()->patch($uri, $action);
    }

    /**
     * Register a DELETE route.
     */
    public static function delete(string $uri, Closure|array $action): void
    {
        static::router()->delete($uri, $action);
    }

    /**
     * Resolve the Router instance from the container.
     */
    protected static function router(): Router
    {
        return Container::getInstance()->make(Router::class);
    }
}
