<?php

declare(strict_types=1);

namespace Fly\Support;

/**
 * String Utility Class.
 */
class Str
{
    protected static array $snakeCache = [];
    protected static array $studlyCache = [];
    protected static array $camelCache = [];

    /**
     * Convert a string to snake_case.
     */
    public static function snake(string $value, string $delimiter = '_'): string
    {
        $key = $value . $delimiter;

        if (isset(static::$snakeCache[$key])) {
            return static::$snakeCache[$key];
        }

        if (!ctype_lower($value)) {
            $value = preg_replace('/\s+/u', '', ucwords($value));
            $value = strtolower(preg_replace('/(.)(?=[A-Z])/u', '$1' . $delimiter, $value));
        }

        return static::$snakeCache[$key] = $value;
    }

    /**
     * Convert a string to StudlyCase (PascalCase).
     */
    public static function studly(string $value): string
    {
        if (isset(static::$studlyCache[$value])) {
            return static::$studlyCache[$value];
        }

        return static::$studlyCache[$value] = str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $value)));
    }

    /**
     * Convert a string to camelCase.
     */
    public static function camel(string $value): string
    {
        if (isset(static::$camelCache[$value])) {
            return static::$camelCache[$value];
        }

        return static::$camelCache[$value] = lcfirst(static::studly($value));
    }

    /**
     * Convert a string to Title Case.
     */
    public static function title(string $value): string
    {
        return mb_convert_case($value, MB_CASE_TITLE, 'UTF-8');
    }

    /**
     * Convert a string to kebab-case.
     */
    public static function kebab(string $value): string
    {
        return static::snake($value, '-');
    }

    /**
     * Determine if a string starts with a given substring.
     */
    public static function startsWith(string $haystack, string|array $needles): bool
    {
        foreach ((array) $needles as $needle) {
            if (str_starts_with($haystack, $needle)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Determine if a string ends with a given substring.
     */
    public static function endsWith(string $haystack, string|array $needles): bool
    {
        foreach ((array) $needles as $needle) {
            if (str_ends_with($haystack, $needle)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Determine if a string contains a given substring.
     */
    public static function contains(string $haystack, string|array $needles): bool
    {
        foreach ((array) $needles as $needle) {
            if (str_contains($haystack, $needle)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Limit the number of characters in a string.
     */
    public static function limit(string $value, int $limit = 100, string $end = '...'): string
    {
        if (mb_strwidth($value, 'UTF-8') <= $limit) {
            return $value;
        }
        return rtrim(mb_strimwidth($value, 0, $limit, '', 'UTF-8')) . $end;
    }

    /**
     * Generate a UUID (v4).
     */
    public static function uuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    /**
     * Generate a random string.
     */
    public static function random(int $length = 16): string
    {
        return substr(bin2hex(random_bytes($length)), 0, $length);
    }

    /**
     * Get the plural form (naive English pluralization).
     */
    public static function plural(string $value): string
    {
        if (str_ends_with($value, 'y') && !str_ends_with($value, 'ey')) {
            return substr($value, 0, -1) . 'ies';
        }
        if (str_ends_with($value, 's') || str_ends_with($value, 'x') || str_ends_with($value, 'ch') || str_ends_with($value, 'sh')) {
            return $value . 'es';
        }
        return $value . 's';
    }

    /**
     * Get the singular form (naive English singularization).
     */
    public static function singular(string $value): string
    {
        if (str_ends_with($value, 'ies')) {
            return substr($value, 0, -3) . 'y';
        }
        if (str_ends_with($value, 'ses') || str_ends_with($value, 'xes') || str_ends_with($value, 'ches') || str_ends_with($value, 'shes')) {
            return substr($value, 0, -2);
        }
        if (str_ends_with($value, 's') && !str_ends_with($value, 'ss')) {
            return substr($value, 0, -1);
        }
        return $value;
    }
}
