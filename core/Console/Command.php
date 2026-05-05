<?php

declare(strict_types=1);

namespace Fly\Console;

/**
 * Base CLI Command.
 */
abstract class Command
{
    /** @var string The console command name. */
    protected string $name = '';

    /** @var string The console command description. */
    protected string $description = '';

    /** @var array<int, string> Command line arguments */
    protected array $args = [];

    /** @var array<string, string|bool> Command line options */
    protected array $options = [];

    /**
     * Execute the console command.
     */
    abstract public function handle(): int;

    /**
     * Parse input and execute the command.
     */
    public function execute(array $argv): int
    {
        $this->parseArgs($argv);
        return $this->handle();
    }

    /**
     * Parse argv into args and options.
     */
    protected function parseArgs(array $argv): void
    {
        $this->args = [];
        $this->options = [];

        foreach ($argv as $arg) {
            if (str_starts_with($arg, '--')) {
                $parts = explode('=', substr($arg, 2), 2);
                $this->options[$parts[0]] = $parts[1] ?? true;
            } elseif (str_starts_with($arg, '-')) {
                $this->options[substr($arg, 1)] = true;
            } else {
                $this->args[] = $arg;
            }
        }
    }

    /**
     * Get an argument by index (0-based after the command name).
     */
    protected function argument(int $index, mixed $default = null): mixed
    {
        return $this->args[$index] ?? $default;
    }

    /**
     * Get an option by name.
     */
    protected function option(string $key, mixed $default = false): mixed
    {
        return $this->options[$key] ?? $default;
    }

    // ----------------------------------------------------------------
    // Output formatting helpers
    // ----------------------------------------------------------------

    protected function info(string $string): void
    {
        echo "\033[32m{$string}\033[0m" . PHP_EOL;
    }

    protected function error(string $string): void
    {
        echo "\033[31m{$string}\033[0m" . PHP_EOL;
    }

    protected function warning(string $string): void
    {
        echo "\033[33m{$string}\033[0m" . PHP_EOL;
    }

    protected function line(string $string): void
    {
        echo $string . PHP_EOL;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }
}
