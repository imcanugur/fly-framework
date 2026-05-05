<?php

declare(strict_types=1);

namespace Fly\Routing;

use Closure;
use Fly\Container\Container;

/**
 * Static route registration facade.
 *
 * Provides the expressive API by delegating to the Router singleton.
 *
 * Usage:
 *   Route::get('/', fn () => 'Hello');
 *   Route::get('/users/{id}', [UserController::class, 'show'])->name('users.show')->whereNumber('id');
 *   Route::group(['prefix' => '/api'], function () { ... });
 */
class Route
{
    public static function get(string $uri, Closure|array $action): RouteEntry
    {
        return static::router()->get($uri, $action);
    }

    public static function post(string $uri, Closure|array $action): RouteEntry
    {
        return static::router()->post($uri, $action);
    }

    public static function put(string $uri, Closure|array $action): RouteEntry
    {
        return static::router()->put($uri, $action);
    }

    public static function patch(string $uri, Closure|array $action): RouteEntry
    {
        return static::router()->patch($uri, $action);
    }

    public static function delete(string $uri, Closure|array $action): RouteEntry
    {
        return static::router()->delete($uri, $action);
    }

    public static function any(string $uri, Closure|array $action): RouteEntry
    {
        return static::router()->any($uri, $action);
    }

    /** @param list<string> $methods */
    public static function match(array $methods, string $uri, Closure|array $action): RouteEntry
    {
        return static::router()->match($methods, $uri, $action);
    }

    /** @param array{prefix?: string, middleware?: list<string>} $attributes */
    public static function group(array $attributes, Closure $callback): void
    {
        static::router()->group($attributes, $callback);
    }

    /**
     * Generate a URL for a named route.
     *
     * @param array<string, string> $params
     */
    public static function url(string $name, array $params = []): string
    {
        return static::router()->url($name, $params);
    }

    protected static function router(): Router
    {
        return Container::getInstance()->make(Router::class);
    }
}
