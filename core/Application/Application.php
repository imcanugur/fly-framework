<?php

declare(strict_types=1);

namespace Fly\Application;

use Fly\Container\Container;

/**
 * The primary framework object.
 *
 * Responsibilities:
 * - Service container management (bind, singleton, make)
 * - Base path resolution
 * - Service provider registration and booting
 * - Framework version information
 */
class Application extends Container
{
    /**
     * The Fly Framework version.
     */
    public const string VERSION = '0.1.0';

    /**
     * The base path of the application.
     */
    protected string $basePath;

    /**
     * All registered service providers.
     *
     * @var array<int, \Fly\Support\ServiceProvider>
     */
    protected array $providers = [];

    /**
     * Indicates if the application has been bootstrapped.
     */
    protected bool $bootstrapped = false;

    /**
     * Create a new Application instance.
     */
    public function __construct(string $basePath)
    {
        $this->basePath = rtrim($basePath, '/');
        $this->registerBaseBindings();
    }

    /**
     * Register the core container bindings.
     */
    protected function registerBaseBindings(): void
    {
        static::setInstance($this);

        $this->instance('app', $this);
        $this->instance(self::class, $this);
        $this->instance(Container::class, $this);
    }

    /**
     * Register a service provider with the application.
     *
     * @param \Fly\Support\ServiceProvider|class-string<\Fly\Support\ServiceProvider> $provider
     */
    public function register(string|object $provider): void
    {
        if (is_string($provider)) {
            $provider = new $provider($this);
        }

        if (method_exists($provider, 'register')) {
            $provider->register();
        }

        $this->providers[] = $provider;
    }

    /**
     * Boot all registered service providers.
     */
    public function boot(): void
    {
        if ($this->bootstrapped) {
            return;
        }

        foreach ($this->providers as $provider) {
            if (method_exists($provider, 'boot')) {
                $provider->boot();
            }
        }

        $this->bootstrapped = true;
    }

    /**
     * Determine if the application has been bootstrapped.
     */
    public function isBootstrapped(): bool
    {
        return $this->bootstrapped;
    }

    // ----------------------------------------------------------------
    // Path Helpers
    // ----------------------------------------------------------------

    /**
     * Get the base path of the application.
     */
    public function basePath(string $path = ''): string
    {
        return $this->basePath . ($path !== '' ? '/' . ltrim($path, '/') : '');
    }

    /**
     * Get the path to the /core directory.
     */
    public function corePath(string $path = ''): string
    {
        return $this->basePath('core' . ($path !== '' ? '/' . ltrim($path, '/') : ''));
    }

    /**
     * Get the path to the /app directory.
     */
    public function appPath(string $path = ''): string
    {
        return $this->basePath('app' . ($path !== '' ? '/' . ltrim($path, '/') : ''));
    }

    /**
     * Get the path to the /config directory.
     */
    public function configPath(string $path = ''): string
    {
        return $this->basePath('config' . ($path !== '' ? '/' . ltrim($path, '/') : ''));
    }

    /**
     * Get the path to the /public directory.
     */
    public function publicPath(string $path = ''): string
    {
        return $this->basePath('public' . ($path !== '' ? '/' . ltrim($path, '/') : ''));
    }

    /**
     * Get the path to the /storage directory.
     */
    public function storagePath(string $path = ''): string
    {
        return $this->basePath('storage' . ($path !== '' ? '/' . ltrim($path, '/') : ''));
    }

    /**
     * Get the path to the /resources directory.
     */
    public function resourcesPath(string $path = ''): string
    {
        return $this->basePath('resources' . ($path !== '' ? '/' . ltrim($path, '/') : ''));
    }

    /**
     * Get the path to the /routes directory.
     */
    public function routesPath(string $path = ''): string
    {
        return $this->basePath('routes' . ($path !== '' ? '/' . ltrim($path, '/') : ''));
    }

    /**
     * Get the framework version.
     */
    public function version(): string
    {
        return static::VERSION;
    }
}
