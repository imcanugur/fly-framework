<?php

declare(strict_types=1);

namespace Fly\Support;

/**
 * A simple environment variable parser.
 *
 * Loads variables from a .env file into putenv(), $_ENV, and $_SERVER.
 */
class Env
{
    /**
     * Load the .env file.
     */
    public static function load(string $path): void
    {
        if (!file_exists($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            $line = trim($line);

            // Skip comments and empty lines
            if (str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $key   = trim($key);
            $value = trim($value);

            // Strip quotes
            if (preg_match('/^"(.*)"$/', $value, $matches) || preg_match("/^'(.*)'$/", $value, $matches)) {
                $value = $matches[1];
            }

            // Set the environment variables
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
            putenv("{$key}={$value}");
        }
    }

    /**
     * Get an environment variable with an optional default.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $value = getenv($key);

        if ($value === false) {
            $value = $_ENV[$key] ?? $_SERVER[$key] ?? null;
        }

        if ($value === null) {
            return $default;
        }

        switch (strtolower((string) $value)) {
            case 'true':
            case '(true)':
                return true;
            case 'false':
            case '(false)':
                return false;
            case 'empty':
            case '(empty)':
                return '';
            case 'null':
            case '(null)':
                return null;
        }

        return $value;
    }
}
