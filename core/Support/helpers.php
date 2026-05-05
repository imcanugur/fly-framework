<?php

declare(strict_types=1);

if (!function_exists('env')) {
    /**
     * Gets the value of an environment variable.
     */
    function env(string $key, mixed $default = null): mixed
    {
        return \Fly\Support\Env::get($key, $default);
    }
}

if (!function_exists('config')) {
    /**
     * Get / set the specified configuration value.
     *
     * If an array is passed as the key, we will assume you want to set an array of values.
     */
    function config(string|array|null $key = null, mixed $default = null): mixed
    {
        $container = \Fly\Container\Container::getInstance();

        if ($container === null) {
            return $default;
        }

        $repository = $container->make('config');

        if ($key === null) {
            return $repository;
        }

        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $repository->set($k, $v);
            }
            return null;
        }

        return $repository->get($key, $default);
    }
}

if (!function_exists('database_path')) {
    /**
     * Get the path to the database directory.
     */
    function database_path(string $path = ''): string
    {
        $app = \Fly\Container\Container::getInstance()->make('app');
        return $app->basePath('database' . ($path ? '/' . ltrim($path, '/') : ''));
    }
}
