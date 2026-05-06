<?php

declare(strict_types=1);

namespace Fly\Database;

/**
 * Base Database Seeder.
 */
abstract class Seeder
{
    /**
     * Run the database seeds.
     */
    abstract public function run(): void;

    /**
     * Call another seeder class.
     */
    public function call(string $class): void
    {
        $seeder = new $class;
        echo "\033[33mSeeding:\033[0m " . basename(str_replace('\\', '/', $class)) . "\n";
        $seeder->run();
        echo "\033[32mSeeded:\033[0m  " . basename(str_replace('\\', '/', $class)) . "\n";
    }
}
