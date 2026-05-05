<?php

declare(strict_types=1);

namespace Fly\Kernel;

use Fly\Application\Application;

/**
 * The Console Kernel.
 *
 * Manages the CLI lifecycle for the `php fly` command.
 * Will be fully implemented in Phase 7 (CLI Runtime).
 */
class ConsoleKernel
{
    public function __construct(
        protected readonly Application $app,
    ) {}

    /**
     * Handle an incoming console command.
     */
    public function handle(array $argv): int
    {
        $this->app->boot();

        // Phase 7 will implement full command dispatching
        $command = $argv[1] ?? 'list';

        echo "Fly Framework v{$this->app->version()}" . PHP_EOL;
        echo "Command: {$command}" . PHP_EOL;
        echo "Console kernel ready. Command system coming in Phase 7." . PHP_EOL;

        return 0;
    }

    /**
     * Terminate the console lifecycle.
     */
    public function terminate(): void
    {
        // Reserved for cleanup
    }
}
