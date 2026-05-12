<?php

declare(strict_types=1);

namespace Fly\Hashing;

use Fly\Application\Application;
use InvalidArgumentException;

class HashManager implements HasherInterface
{
    /**
     * The application instance.
     */
    protected Application $app;

    /**
     * The array of resolved hashers.
     */
    protected array $hashers = [];

    /**
     * Create a new hash manager instance.
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Get a hasher instance by name.
     */
    public function driver(?string $name = null): HasherInterface
    {
        $name = $name ?: $this->getDefaultDriver();

        return $this->hashers[$name] ??= $this->createDriver($name);
    }

    /**
     * Create a new hasher instance.
     */
    protected function createDriver(string $name): HasherInterface
    {
        $method = 'create' . ucfirst($name) . 'Driver';

        if (method_exists($this, $method)) {
            return $this->$method();
        }

        throw new InvalidArgumentException("Hasher [{$name}] is not supported.");
    }

    /**
     * Create an instance of the Bcrypt hasher driver.
     */
    protected function createBcryptDriver(): BcryptHasher
    {
        return new BcryptHasher();
    }

    /**
     * Get the default hasher driver name.
     */
    public function getDefaultDriver(): string
    {
        return $this->app->make('config')->get('hashing.driver', 'bcrypt');
    }

    /**
     * {@inheritdoc}
     */
    public function make(string $value, array $options = []): string
    {
        return $this->driver()->make($value, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function check(string $value, string $hashedValue, array $options = []): bool
    {
        return $this->driver()->check($value, $hashedValue, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function needsRehash(string $hashedValue, array $options = []): bool
    {
        return $this->driver()->needsRehash($hashedValue, $options);
    }
}
