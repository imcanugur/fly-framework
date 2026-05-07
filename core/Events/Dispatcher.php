<?php

declare(strict_types=1);

namespace Fly\Events;

use Fly\Container\Container;

class Dispatcher
{
    protected array $listeners = [];
    protected array $wildcards = [];
    protected ?Container $container;

    public function __construct(Container $container = null)
    {
        $this->container = $container ?: Container::getInstance();
    }

    public function listen(string|array $events, mixed $listener): void
    {
        foreach ((array) $events as $event) {
            if (str_contains($event, '*')) {
                $this->wildcards[$event][] = $listener;
            } else {
                $this->listeners[$event][] = $listener;
            }
        }
    }

    public function dispatch(string|object $event, mixed $payload = []): array
    {
        // If event is an object, use its class name as the event name
        if (is_object($event)) {
            $payload = $event;
            $event = get_class($event);
        }

        $responses = [];

        foreach ($this->getListeners($event) as $listener) {
            $responses[] = $this->invokeListener($listener, $event, $payload);
        }

        return $responses;
    }

    protected function getListeners(string $event): array
    {
        $listeners = $this->listeners[$event] ?? [];

        foreach ($this->wildcards as $key => $wildcardListeners) {
            if (fnmatch($key, $event)) {
                $listeners = array_merge($listeners, $wildcardListeners);
            }
        }

        return $listeners;
    }

    protected function invokeListener(mixed $listener, string $event, mixed $payload): mixed
    {
        if (is_callable($listener)) {
            return $listener($payload, $event);
        }

        if (is_string($listener) && str_contains($listener, '@')) {
            [$class, $method] = explode('@', $listener);
            $instance = $this->container->make($class);
            return $instance->{$method}($payload, $event);
        }

        // Handle class name (default to 'handle' method)
        if (is_string($listener) && class_exists($listener)) {
            $instance = $this->container->make($listener);
            return $instance->handle($payload, $event);
        }

        return null;
    }

    public function forget(string $event): void
    {
        if (str_contains($event, '*')) {
            unset($this->wildcards[$event]);
        } else {
            unset($this->listeners[$event]);
        }
    }

    public function hasListeners(string $event): bool
    {
        return isset($this->listeners[$event]) || isset($this->wildcards[$event]);
    }
}
