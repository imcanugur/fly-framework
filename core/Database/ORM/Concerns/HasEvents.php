<?php

declare(strict_types=1);

namespace Fly\Database\ORM\Concerns;

use Fly\Container\Container;

trait HasEvents
{
    /**
     * The event dispatcher instance.
     */
    protected static $dispatcher;

    /**
     * Fire the given event for the model.
     *
     * @param string $event
     * @param bool $halt
     * @return mixed
     */
    protected function fireModelEvent(string $event, bool $halt = true): mixed
    {
        if (!isset(static::$dispatcher)) {
            static::$dispatcher = Container::getInstance()->make('events');
        }

        $eventName = "model.{$event}: " . static::class;

        return static::$dispatcher->dispatch($eventName, $this);
    }

    /**
     * Register a model event with the dispatcher.
     *
     * @param string $event
     * @param \Closure|string $callback
     * @return void
     */
    protected static function registerModelEvent(string $event, $callback): void
    {
        if (isset(static::$dispatcher)) {
            static::$dispatcher->listen("model.{$event}: " . static::class, $callback);
        }
    }

    public static function setEventDispatcher($dispatcher): void
    {
        static::$dispatcher = $dispatcher;
    }

    public static function getEventDispatcher()
    {
        return static::$dispatcher;
    }
}
