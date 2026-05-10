<?php

declare(strict_types=1);

namespace Fly\Cache;

use Fly\Application\Application;
use Fly\Cache\Stores\ArrayStore;
use Fly\Cache\Stores\FileStore;
use Fly\Cache\Stores\DatabaseStore;
use InvalidArgumentException;

class CacheManager
{
    /**
     * The application instance.
     */
    protected Application $app;

    /**
     * The array of resolved cache connections.
     */
    protected array $stores = [];

    /**
     * Create a new cache manager instance.
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Get a cache store instance by name.
     */
    public function store(?string $name = null): CacheRepositoryInterface
    {
        $name = $name ?: $this->getDefaultDriver();

        return $this->stores[$name] ??= $this->resolve($name);
    }

    /**
     * Resolve the given store.
     */
    protected function resolve(string $name): CacheRepositoryInterface
    {
        $config = $this->getConfig($name);

        if (is_null($config)) {
            throw new InvalidArgumentException("Cache store [{$name}] is not defined.");
        }

        $driverMethod = 'create' . ucfirst($config['driver']) . 'Driver';

        if (method_exists($this, $driverMethod)) {
            return $this->repository($this->$driverMethod($config));
        }

        throw new InvalidArgumentException("Driver [{$config['driver']}] is not supported.");
    }

    /**
     * Create a new cache repository with the given store.
     */
    public function repository(CacheStoreInterface $store): Repository
    {
        return new Repository($store);
    }

    /**
     * Create an instance of the File cache driver.
     */
    protected function createFileDriver(array $config): FileStore
    {
        return new FileStore($config['path']);
    }

    /**
     * Create an instance of the Array cache driver.
     */
    protected function createArrayDriver(array $config): ArrayStore
    {
        return new ArrayStore();
    }

    /**
     * Create an instance of the Database cache driver.
     */
    protected function createDatabaseDriver(array $config): DatabaseStore
    {
        return new DatabaseStore(
            $this->app->make('db')->connection($config['connection'] ?? null),
            $config['table'],
            $this->getDefaultPrefix()
        );
    }

    /**
     * Get the default cache driver name.
     */
    public function getDefaultDriver(): string
    {
        return $this->app->make('config')->get('cache.default', 'file');
    }

    /**
     * Get the cache connection configuration.
     */
    protected function getConfig(string $name): ?array
    {
        return $this->app->make('config')->get("cache.stores.{$name}");
    }

    /**
     * Get the default cache key prefix.
     */
    protected function getDefaultPrefix(): string
    {
        return $this->app->make('config')->get('cache.prefix', 'fly_cache');
    }

    /**
     * Dynamically call the default driver instance.
     */
    public function __call(string $method, array $parameters): mixed
    {
        return $this->store()->$method(...$parameters);
    }
}
