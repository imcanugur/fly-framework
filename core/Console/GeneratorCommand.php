<?php

declare(strict_types=1);

namespace Fly\Console;

/**
 * Base class for commands that generate files.
 */
abstract class GeneratorCommand extends Command
{
    /**
     * Get the stub file content for the generator.
     */
    abstract protected function getStub(): string;

    /**
     * Get the default namespace for the class.
     */
    abstract protected function getDefaultNamespace(): string;

    /**
     * Get the destination file path.
     */
    abstract protected function getPath(string $name): string;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $name = $this->argument(0);

        if (empty($name)) {
            $this->error("Missing required argument: name");
            return 1;
        }

        $path = $this->getPath($name);

        if (file_exists($path)) {
            $this->error("File already exists: {$path}");
            return 1;
        }

        $this->makeDirectory(dirname($path));

        $content = $this->buildClass($name);

        file_put_contents($path, $content);

        $this->info("Successfully created: {$path}");

        return 0;
    }

    /**
     * Build the class with the given name.
     */
    protected function buildClass(string $name): string
    {
        $stub = $this->getStub();

        // Extract class name (without namespace path)
        $className = basename(str_replace('\\', '/', $name));

        // Create full namespace
        $namespace = $this->getDefaultNamespace();
        $subNamespace = dirname(str_replace('/', '\\', $name));
        
        if ($subNamespace !== '.') {
            $namespace .= '\\' . $subNamespace;
        }

        return str_replace(
            ['{{ namespace }}', '{{ class }}'],
            [$namespace, $className],
            $stub
        );
    }

    /**
     * Build the directory for the class if necessary.
     */
    protected function makeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }
    }
}
