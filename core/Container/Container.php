<?php

declare(strict_types=1);

namespace Fly\Container;

use Closure;
use ReflectionClass;
use ReflectionParameter;
use RuntimeException;

/**
 * The framework's dependency injection container.
 *
 * Provides bind, singleton, instance, and make operations
 * with automatic constructor dependency resolution via Reflection.
 */
class Container
{
    /**
     * The global container instance.
     */
    protected static ?self $instance = null;

    /**
     * Registered bindings (factories).
     *
     * @var array<string, array{concrete: Closure, shared: bool}>
     */
    protected array $bindings = [];

    /**
     * Resolved singleton instances.
     *
     * @var array<string, mixed>
     */
    protected array $instances = [];

    /**
     * Set the global container instance.
     */
    public static function setInstance(?self $container): void
    {
        static::$instance = $container;
    }

    /**
     * Get the global container instance.
     */
    public static function getInstance(): ?self
    {
        return static::$instance;
    }

    /**
     * Register a binding in the container.
     */
    public function bind(string $abstract, Closure|string|null $concrete = null): void
    {
        $concrete = $this->normalizeConcrete($abstract, $concrete);

        $this->bindings[$abstract] = [
            'concrete' => $concrete,
            'shared'   => false,
        ];
    }

    /**
     * Register a shared (singleton) binding.
     */
    public function singleton(string $abstract, Closure|string|null $concrete = null): void
    {
        $concrete = $this->normalizeConcrete($abstract, $concrete);

        $this->bindings[$abstract] = [
            'concrete' => $concrete,
            'shared'   => true,
        ];
    }

    /**
     * Register an existing instance in the container.
     */
    public function instance(string $abstract, mixed $instance): void
    {
        $this->instances[$abstract] = $instance;
    }

    /**
     * Resolve a type from the container.
     */
    public function make(string $abstract): mixed
    {
        // Return cached singleton instance if available
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        // Check for a registered binding
        if (isset($this->bindings[$abstract])) {
            $binding  = $this->bindings[$abstract];
            $instance = ($binding['concrete'])($this);

            if ($binding['shared']) {
                $this->instances[$abstract] = $instance;
            }

            return $instance;
        }

        // Attempt automatic resolution via reflection
        return $this->resolve($abstract);
    }

    /**
     * Determine if a binding or instance exists.
     */
    public function has(string $abstract): bool
    {
        return isset($this->bindings[$abstract]) || isset($this->instances[$abstract]);
    }

    // ----------------------------------------------------------------
    // Internal Resolution
    // ----------------------------------------------------------------

    /**
     * Resolve a class and its dependencies via reflection.
     */
    protected function resolve(string $class): object
    {
        if (!class_exists($class)) {
            throw new RuntimeException(
                "Container: unable to resolve [{$class}]. Class does not exist."
            );
        }

        $reflector = new ReflectionClass($class);

        if (!$reflector->isInstantiable()) {
            throw new RuntimeException(
                "Container: [{$class}] is not instantiable."
            );
        }

        $constructor = $reflector->getConstructor();

        if ($constructor === null) {
            return new $class();
        }

        $dependencies = array_map(
            fn(ReflectionParameter $param) => $this->resolveDependency($param),
            $constructor->getParameters()
        );

        return $reflector->newInstanceArgs($dependencies);
    }

    /**
     * Resolve a single constructor parameter.
     */
    protected function resolveDependency(ReflectionParameter $param): mixed
    {
        $type = $param->getType();

        // If the parameter has a class type-hint, resolve it
        if ($type !== null && !$type->isBuiltin()) {
            return $this->make($type->getName());
        }

        // If the parameter has a default value, use it
        if ($param->isDefaultValueAvailable()) {
            return $param->getDefaultValue();
        }

        throw new RuntimeException(
            "Container: unable to resolve parameter [{$param->getName()}] in class [{$param->getDeclaringClass()?->getName()}]."
        );
    }

    /**
     * Normalize the concrete to a Closure.
     */
    protected function normalizeConcrete(string $abstract, Closure|string|null $concrete): Closure
    {
        if ($concrete === null) {
            return fn(Container $c) => $c->resolve($abstract);
        }

        if (is_string($concrete)) {
            return fn(Container $c) => $c->resolve($concrete);
        }

        return $concrete;
    }
}
