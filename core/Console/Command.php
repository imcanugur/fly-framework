<?php

declare(strict_types=1);

namespace Fly\Console;

/**
 * Base CLI Command.
 */
abstract class Command
{
    /** @var string The console command signature. */
    protected string $signature = '';

    /** @var string The console command name. */
    protected string $name = '';

    /** @var string The console command description. */
    protected string $description = '';

    /** @var array<string, mixed> Parsed command line arguments */
    protected array $args = [];

    /** @var array<string, mixed> Parsed command line options */
    protected array $options = [];

    /** @var array<int, array> Signature defined arguments */
    protected array $expectedArguments = [];

    /** @var array<string, array> Signature defined options */
    protected array $expectedOptions = [];

    public function __construct()
    {
        if ($this->signature) {
            [$this->name, $this->expectedArguments, $this->expectedOptions] = Parser::parse($this->signature);
        }
    }



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
     * Parse argv into args and options based on signature definition.
     */
    protected function parseArgs(array $argv): void
    {
        $this->args = [];
        $this->options = [];

        // Set default options to false
        foreach ($this->expectedOptions as $opt => $def) {
            $this->options[$opt] = false;
        }

        $argIndex = 0;

        foreach ($argv as $arg) {
            if (str_starts_with($arg, '--')) {
                $parts = explode('=', substr($arg, 2), 2);
                $optName = $parts[0];
                $this->options[$optName] = $parts[1] ?? true;
            } elseif (str_starts_with($arg, '-')) {
                $optName = substr($arg, 1);
                $this->options[$optName] = true;
            } else {
                if (isset($this->expectedArguments[$argIndex])) {
                    $argName = $this->expectedArguments[$argIndex]['name'];
                    $this->args[$argName] = $arg;
                } else {
                    // Positional fallback
                    $this->args[$argIndex] = $arg;
                }
                $argIndex++;
            }
        }
    }

    /**
     * Get an argument by name or index.
     */
    public function argument(string|int $key, mixed $default = null): mixed
    {
        return $this->args[$key] ?? $default;
    }

    /**
     * Get an option by name.
     */
    public function option(string $key, mixed $default = false): mixed
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

    // ----------------------------------------------------------------
    // Interactive Prompts
    // ----------------------------------------------------------------

    /**
     * Prompt the user for input.
     */
    public function ask(string $question, string $default = ''): string
    {
        $this->info($question . ($default ? " [{$default}]" : '') . ': ');
        $handle = fopen("php://stdin", "r");
        $line = fgets($handle);
        fclose($handle);
        $answer = trim($line !== false ? $line : '');
        return $answer === '' ? $default : $answer;
    }

    /**
     * Prompt the user for confirmation (Y/n).
     */
    public function confirm(string $question, bool $default = false): bool
    {
        $defStr = $default ? 'Y/n' : 'y/N';
        $this->warning("{$question} ({$defStr}): ");
        $handle = fopen("php://stdin", "r");
        $line = fgets($handle);
        fclose($handle);
        $answer = strtolower(trim($line !== false ? $line : ''));

        if ($answer === '') {
            return $default;
        }

        return in_array($answer, ['y', 'yes', 'true', '1'], true);
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
