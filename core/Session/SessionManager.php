<?php

declare(strict_types=1);

namespace Fly\Session;

use Fly\Application\Application;
use Fly\Session\Handlers\FileSessionHandler;
use InvalidArgumentException;

class SessionManager
{
    /**
     * The application instance.
     */
    protected Application $app;

    /**
     * The array of resolved session drivers.
     */
    protected array $drivers = [];

    /**
     * Create a new session manager instance.
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Get a session driver instance.
     */
    public function driver(?string $driver = null): Store
    {
        $driver = $driver ?: $this->getDefaultDriver();

        return $this->drivers[$driver] ??= $this->createDriver($driver);
    }

    /**
     * Create a new session driver instance.
     */
    protected function createDriver(string $driver): Store
    {
        $method = 'create' . ucfirst($driver) . 'Driver';

        if (method_exists($this, $method)) {
            return $this->$method();
        }

        throw new InvalidArgumentException("Driver [{$driver}] is not supported.");
    }

    /**
     * Create an instance of the "file" session driver.
     */
    protected function createFileDriver(): Store
    {
        $config = $this->app->make('config')->get('session');

        return $this->buildSession(new FileSessionHandler(
            $config['files'], $config['lifetime']
        ));
    }

    /**
     * Build the session instance.
     */
    protected function buildSession(\SessionHandlerInterface $handler): Store
    {
        $config = $this->app->make('config')->get('session');

        return new Store($config['cookie'], $handler);
    }

    /**
     * Get the default session driver name.
     */
    public function getDefaultDriver(): string
    {
        return $this->app->make('config')->get('session.driver', 'file');
    }

    /**
     * Dynamically call the default driver instance.
     */
    public function __call(string $method, array $parameters): mixed
    {
        return $this->driver()->$method(...$parameters);
    }
}
