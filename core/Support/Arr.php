<?php

declare(strict_types=1);

namespace Fly\Support;

class Arr
{
    public static function except(array $array, array|string $keys): array
    {
        $keys = (array) $keys;
        foreach ($keys as $key) {
            unset($array[$key]);
        }
        return $array;
    }

    public static function only(array $array, array|string $keys): array
    {
        return array_intersect_key($array, array_flip((array) $keys));
    }
    
    public static function isAssoc(array $array): bool
    {
        $keys = array_keys($array);
        return array_keys($keys) !== $keys;
    }
}
