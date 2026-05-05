<?php

declare(strict_types=1);

namespace Fly\Support\Facades;

use Fly\Container\Container;
use RuntimeException;

/**
 * Base Facade class.
 */
abstract class Facade
{
    protected static ?Container $app = null;

    /**
     * Set the application instance.
     */
    public static function setFacadeApplication(Container $app): void
    {
        static::$app = $app;
    }

    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        throw new RuntimeException("Facade does not implement getFacadeAccessor method.");
    }

    /**
     * Handle dynamic, static calls to the object.
     */
    public static function __callStatic(string $method, array $args): mixed
    {
        $instance = static::$app->make(static::getFacadeAccessor());

        if (!$instance) {
            throw new RuntimeException("A facade root has not been set.");
        }

        return $instance->$method(...$args);
    }
}
