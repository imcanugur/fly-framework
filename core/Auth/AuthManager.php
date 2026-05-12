<?php

declare(strict_types=1);

namespace Fly\Auth;

use Fly\Application\Application;
use InvalidArgumentException;

class AuthManager
{
    /**
     * The application instance.
     */
    protected Application $app;

    /**
     * The array of resolved guards.
     */
    protected array $guards = [];

    /**
     * The user provider custom creators.
     */
    protected array $customProviderCreators = [];

    /**
     * Create a new authentication manager instance.
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Get a guard instance by name.
     */
    public function guard(?string $name = null): GuardInterface
    {
        $name = $name ?: $this->getDefaultDriver();

        return $this->guards[$name] ??= $this->resolve($name);
    }

    /**
     * Resolve the given guard.
     */
    protected function resolve(string $name): GuardInterface
    {
        $config = $this->getConfig($name);

        if (is_null($config)) {
            throw new InvalidArgumentException("Auth guard [{$name}] is not defined.");
        }

        $method = 'create' . ucfirst($config['driver']) . 'Driver';

        if (method_exists($this, $method)) {
            return $this->$method($name, $config);
        }

        throw new InvalidArgumentException("Auth driver [{$config['driver']}] is not supported.");
    }

    /**
     * Create a session based authentication guard.
     */
    protected function createSessionDriver(string $name, array $config): SessionGuard
    {
        $provider = $this->createUserProvider($config['provider'] ?? null);

        return new SessionGuard($name, $provider, $this->app->make('session')->driver());
    }

    /**
     * Create a token based authentication guard.
     */
    protected function createTokenDriver(string $name, array $config): TokenGuard
    {
        $provider = $this->createUserProvider($config['provider'] ?? null);

        return new TokenGuard($provider, $this->app->make('request'));
    }

    /**
     * Create the user provider implementation for the driver.
     */
    public function createUserProvider(?string $provider = null): UserProviderInterface
    {
        $config = $this->app->make('config')->get('auth.providers.' . ($provider ?: 'users'));

        if (is_null($config)) {
            throw new InvalidArgumentException("Authentication user provider [{$provider}] is not defined.");
        }

        $driver = $config['driver'];

        switch ($driver) {
            case 'database':
                return $this->createDatabaseProvider($config);
            case 'eloquent':
                return $this->createEloquentProvider($config);
            default:
                throw new InvalidArgumentException("Authentication user provider driver [{$driver}] is not supported.");
        }
    }

    /**
     * Create a database user provider.
     */
    protected function createDatabaseProvider(array $config): DatabaseUserProvider
    {
        $connection = $this->app->make('db')->connection($config['connection'] ?? null);

        return new DatabaseUserProvider($connection, $this->app->make('hash'), $config['table']);
    }

    /**
     * Create an Eloquent user provider.
     */
    protected function createEloquentProvider(array $config): EloquentUserProvider
    {
        return new EloquentUserProvider($this->app->make('hash'), $config['model']);
    }

    /**
     * Get the default authentication driver name.
     */
    public function getDefaultDriver(): string
    {
        return $this->app->make('config')->get('auth.defaults.guard');
    }

    /**
     * Get the guard configuration.
     */
    protected function getConfig(string $name): ?array
    {
        return $this->app->make('config')->get("auth.guards.{$name}");
    }

    /**
     * Dynamically call the default guard instance.
     */
    public function __call(string $method, array $parameters): mixed
    {
        return $this->guard()->$method(...$parameters);
    }
}
