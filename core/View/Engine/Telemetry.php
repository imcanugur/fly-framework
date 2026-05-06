<?php

declare(strict_types=1);

namespace Fly\View\Engine;

class Telemetry
{
    protected static array $measurements = [];

    public static function start(string $label): void
    {
        self::$measurements[$label] = [
            'start' => microtime(true),
            'memory_start' => memory_get_usage()
        ];
    }

    public static function end(string $label = null): array
    {
        if ($label === null) {
            $label = array_key_last(self::$measurements);
        }
        
        if ($label === null || !isset(self::$measurements[$label])) {
            return ['duration' => 0, 'memory' => 0];
        }

        $data = self::$measurements[$label];
        $duration = microtime(true) - $data['start'];
        $memory = memory_get_usage() - $data['memory_start'];

        unset(self::$measurements[$label]);

        return [
            'duration' => $duration * 1000, // ms
            'memory' => $memory / 1024, // kb
        ];
    }

    public static function all(): array
    {
        return self::$measurements;
    }
}
