<?php

declare(strict_types=1);

namespace Fly\Session;

use Fly\Support\Str;

class Store
{
    /**
     * The session ID.
     */
    protected string $id;

    /**
     * The session name.
     */
    protected string $name;

    /**
     * The session attributes.
     */
    protected array $attributes = [];

    /**
     * The session handler.
     */
    protected \SessionHandlerInterface $handler;

    /**
     * Indicates if the session has been started.
     */
    protected bool $started = false;

    /**
     * Create a new session store instance.
     */
    public function __construct(string $name, \SessionHandlerInterface $handler, ?string $id = null)
    {
        $this->name = $name;
        $this->handler = $handler;
        $this->setId($id);
    }

    /**
     * Start the session, reading the data from the handler.
     */
    public function start(): bool
    {
        $this->loadSession();
        return $this->started = true;
    }

    /**
     * Load the session data from the handler.
     */
    protected function loadSession(): void
    {
        $this->attributes = array_merge(
            $this->attributes,
            $this->readFromHandler()
        );
    }

    /**
     * Read the session data from the handler.
     */
    protected function readFromHandler(): array
    {
        $data = $this->handler->read($this->id);

        if ($data) {
            return unserialize($data) ?: [];
        }

        return [];
    }

    /**
     * Save the session data to the handler.
     */
    public function save(): void
    {
        $this->handler->write($this->id, serialize($this->attributes));
        $this->started = false;
    }

    /**
     * Get all of the session attributes.
     */
    public function all(): array
    {
        return $this->attributes;
    }

    /**
     * Get an item from the session.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }

    /**
     * Put a key / value pair or array of key / value pairs in the session.
     */
    public function put(string|array $key, mixed $value = null): void
    {
        if (!is_array($key)) {
            $key = [$key => $value];
        }

        foreach ($key as $arrayKey => $arrayValue) {
            $this->attributes[$arrayKey] = $arrayValue;
        }
    }

    /**
     * Remove an item from the session, returning its value.
     */
    public function pull(string $key, mixed $default = null): mixed
    {
        $value = $this->get($key, $default);
        $this->forget($key);
        return $value;
    }

    /**
     * Remove one or many items from the session.
     */
    public function forget(string|array $keys): void
    {
        foreach ((array) $keys as $key) {
            unset($this->attributes[$key]);
        }
    }

    /**
     * Remove all items from the session.
     */
    public function flush(): void
    {
        $this->attributes = [];
    }

    /**
     * Generate a new session ID.
     */
    public function regenerate(bool $destroy = false): bool
    {
        if ($destroy) {
            $this->handler->destroy($this->id);
        }

        $this->setId($this->generateSessionId());

        return true;
    }

    /**
     * Get the session ID.
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Set the session ID.
     */
    public function setId(?string $id): void
    {
        $this->id = $id ?: $this->generateSessionId();
    }

    /**
     * Get a new random session ID.
     */
    protected function generateSessionId(): string
    {
        return Str::random(40);
    }

    /**
     * Get the session name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Determine if the session has been started.
     */
    public function isStarted(): bool
    {
        return $this->started;
    }

    /**
     * Flash a key / value pair to the session.
     */
    public function flash(string $key, mixed $value): void
    {
        $this->put($key, $value);
        $this->push('_flash.new', $key);
        $this->removeFromOldFlashData([$key]);
    }

    /**
     * Push a value onto a session array.
     */
    public function push(string $key, mixed $value): void
    {
        $array = $this->get($key, []);
        $array[] = $value;
        $this->put($key, $array);
    }

    /**
     * Remove a specific value from the old flash data.
     */
    protected function removeFromOldFlashData(array $keys): void
    {
        $this->put('_flash.old', array_diff($this->get('_flash.old', []), $keys));
    }

    /**
     * Age the flash data for the session.
     */
    public function ageFlashData(): void
    {
        $this->forget($this->get('_flash.old', []));
        $this->put('_flash.old', $this->get('_flash.new', []));
        $this->put('_flash.new', []);
    }
}
