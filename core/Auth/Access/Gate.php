<?php

declare(strict_types=1);

namespace Fly\Auth\Access;

use Fly\Auth\AuthenticatableInterface;
use Fly\Application\Application;
use Closure;
use InvalidArgumentException;

class Gate
{
    /**
     * The application instance.
     */
    protected Application $app;

    /**
     * The user resolver callable.
     */
    protected $userResolver;

    /**
     * All of the defined abilities.
     */
    protected array $abilities = [];

    /**
     * All of the defined policies.
     */
    protected array $policies = [];

    /**
     * All of the before callbacks.
     */
    protected array $beforeCallbacks = [];

    /**
     * All of the after callbacks.
     */
    protected array $afterCallbacks = [];

    /**
     * Create a new gate instance.
     */
    public function __construct(Application $app, callable $userResolver)
    {
        $this->app = $app;
        $this->userResolver = $userResolver;
    }

    /**
     * Create a new gate instance for the given user.
     */
    public function forUser(AuthenticatableInterface $user): static
    {
        $callback = function () use ($user) {
            return $user;
        };

        return new static($this->app, $callback);
    }

    /**
     * Determine if a given ability has been defined.
     */
    public function has(string $ability): bool
    {
        return isset($this->abilities[$ability]);
    }

    /**
     * Define a new ability.
     */
    public function define(string $ability, callable|string $callback): static
    {
        $this->abilities[$ability] = $callback;
        return $this;
    }

    /**
     * Define a policy class for a given class type.
     */
    public function policy(string $class, string $policy): static
    {
        $this->policies[$class] = $policy;
        return $this;
    }

    /**
     * Define a "before" callback.
     */
    public function before(callable $callback): static
    {
        $this->beforeCallbacks[] = $callback;
        return $this;
    }

    /**
     * Define an "after" callback.
     */
    public function after(callable $callback): static
    {
        $this->afterCallbacks[] = $callback;
        return $this;
    }

    /**
     * Inspect the user for the given ability.
     */
    public function inspect(string $ability, mixed $arguments = []): Response
    {
        $user = $this->resolveUser();

        $arguments = (array) $arguments;

        // Run before hooks
        foreach ($this->beforeCallbacks as $callback) {
            if (!is_null($result = $callback($user, $ability, $arguments))) {
                return $this->allowIf($result);
            }
        }

        // Run primary check
        $result = $this->raw($ability, $arguments);

        // Run after hooks
        foreach ($this->afterCallbacks as $callback) {
            if (!is_null($afterResult = $callback($user, $ability, $arguments, $result))) {
                $result = $afterResult;
            }
        }

        return $this->allowIf($result);
    }

    /**
     * Get the raw result for the given ability.
     */
    protected function raw(string $ability, array $arguments): mixed
    {
        $user = $this->resolveUser();

        if (!$user) {
            return false;
        }

        if (isset($this->abilities[$ability])) {
            return $this->callAuthCallback($user, $ability, $arguments);
        }

        return $this->checkPolicy($user, $ability, $arguments);
    }

    /**
     * Convert a result to an Access Response.
     */
    protected function allowIf(mixed $result): Response
    {
        if ($result instanceof Response) {
            return $result;
        }

        return $result ? Response::allow() : Response::deny();
    }

    /**
     * Determine if the given ability should be granted for the current user.
     */
    public function check(string $ability, mixed $arguments = []): bool
    {
        return $this->inspect($ability, $arguments)->allowed();
    }

    /**
     * Resolve the user from the user resolver.
     */
    protected function resolveUser(): ?AuthenticatableInterface
    {
        return ($this->userResolver)();
    }

    /**
     * Call the authentication callback.
     */
    protected function callAuthCallback(AuthenticatableInterface $user, string $ability, array $arguments): bool
    {
        $callback = $this->abilities[$ability];

        if (is_string($callback) && str_contains($callback, '@')) {
            [$class, $method] = explode('@', $callback);
            return $this->app->make($class)->$method($user, ...$arguments);
        }

        return $callback($user, ...$arguments);
    }

    /**
     * Check the policies for the given ability and arguments.
     */
    protected function checkPolicy(AuthenticatableInterface $user, string $ability, array $arguments): bool
    {
        if (empty($arguments)) {
            return false;
        }

        $class = is_object($arguments[0]) ? get_class($arguments[0]) : $arguments[0];

        if (!isset($this->policies[$class])) {
            return false;
        }

        $policy = $this->app->make($this->policies[$class]);

        return $policy->$ability($user, ...$arguments);
    }
}
