<?php

declare(strict_types=1);

namespace Fly\Config;

/**
 * Centralized Configuration Repository.
 *
 * Manages configuration loaded from files with support for dot-notation access.
 */
class Repository
{
    /**
     * All configuration items.
     *
     * @var array<string, mixed>
     */
    protected array $items = [];

    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    /**
     * Determine if the given configuration value exists.
     */
    public function has(string $key): bool
    {
        return $this->get($key, $this) !== $this;
    }

    /**
     * Get the specified configuration value.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $array = $this->items;

        if (isset($array[$key])) {
            return $array[$key];
        }

        foreach (explode('.', $key) as $segment) {
            if (is_array($array) && array_key_exists($segment, $array)) {
                $array = $array[$segment];
            } else {
                return $default;
            }
        }

        return $array;
    }

    /**
     * Set a given configuration value.
     */
    public function set(string $key, mixed $value): void
    {
        $keys = explode('.', $key);
        $array = &$this->items;

        while (count($keys) > 1) {
            $key = array_shift($keys);

            if (!isset($array[$key]) || !is_array($array[$key])) {
                $array[$key] = [];
            }

            $array = &$array[$key];
        }

        $array[array_shift($keys)] = $value;
    }

    /**
     * Get all configuration items.
     */
    public function all(): array
    {
        return $this->items;
    }

    /**
     * Replace all items in the repository.
     */
    public function setAll(array $items): void
    {
        $this->items = $items;
    }

    /**
     * Load all configuration files from the given directory.
     */
    public function loadFromDir(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $files = glob($path . '/*.php');
        if ($files === false) {
            return;
        }

        foreach ($files as $file) {
            $key = basename($file, '.php');
            $this->items[$key] = require $file;
        }
    }
}
