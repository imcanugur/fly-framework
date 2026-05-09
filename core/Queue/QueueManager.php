<?php

declare(strict_types=1);

namespace Fly\Queue;

use Fly\Application\Application;
use Fly\Queue\Drivers\SyncQueue;
use Fly\Queue\Drivers\DatabaseQueue;
use InvalidArgumentException;

class QueueManager
{
    /**
     * The application instance.
     *
     * @var Application
     */
    protected Application $app;

    /**
     * The array of active queue connections.
     *
     * @var array
     */
    protected array $connections = [];

    /**
     * Create a new queue manager instance.
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Get a queue connection instance.
     *
     * @param string|null $name
     * @return QueueInterface
     */
    public function connection(?string $name = null): QueueInterface
    {
        $name = $name ?: $this->getDefaultDriver();

        if (!isset($this->connections[$name])) {
            $this->connections[$name] = $this->resolve($name);
        }

        return $this->connections[$name];
    }

    /**
     * Resolve a queue connection instance.
     *
     * @param string $name
     * @return QueueInterface
     */
    protected function resolve(string $name): QueueInterface
    {
        $config = $this->app->config->get("queue.connections.{$name}");

        if (is_null($config)) {
            throw new InvalidArgumentException("Queue connection [{$name}] is not defined.");
        }

        $driverMethod = 'create' . ucfirst($config['driver']) . 'Driver';

        if (method_exists($this, $driverMethod)) {
            return $this->$driverMethod($config);
        }

        throw new InvalidArgumentException("Unsupported queue driver [{$config['driver']}].");
    }

    /**
     * Create a sync queue driver instance.
     *
     * @param array $config
     * @return SyncQueue
     */
    protected function createSyncDriver(array $config): SyncQueue
    {
        return new SyncQueue($this->app);
    }

    /**
     * Create a database queue driver instance.
     *
     * @param array $config
     * @return DatabaseQueue
     */
    protected function createDatabaseDriver(array $config): DatabaseQueue
    {
        return new DatabaseQueue(
            $this->app->db->connection($config['connection'] ?? null),
            $config['table'],
            $config['queue'] ?? 'default',
            $config['retry_after'] ?? 60
        );
    }

    /**
     * Get the default queue driver name.
     *
     * @return string
     */
    public function getDefaultDriver(): string
    {
        return $this->app->config->get('queue.default');
    }

    /**
     * Pass dynamic methods to the default connection.
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function __call(string $method, array $parameters)
    {
        return $this->connection()->$method(...$parameters);
    }
}
