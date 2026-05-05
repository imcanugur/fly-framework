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
    /**
     * The built-in framework commands.
     *
     * @var array<string, class-string<\Fly\Console\Command>>
     */
    protected array $commands = [
        'make:controller' => \Fly\Console\Commands\MakeControllerCommand::class,
        'make:middleware' => \Fly\Console\Commands\MakeMiddlewareCommand::class,
        'make:model'      => \Fly\Console\Commands\MakeModelCommand::class,
        'make:migration'  => \Fly\Console\Commands\MakeMigrationCommand::class,
    ];

    public function __construct(
        protected readonly Application $app,
    ) {}

    /**
     * Handle an incoming console command.
     */
    public function handle(array $argv): int
    {
        $this->app->bootstrap();

        $commandName = $argv[1] ?? 'list';

        if ($commandName === 'list') {
            $this->listCommands();
            return 0;
        }

        if (!isset($this->commands[$commandName])) {
            echo "\033[31mCommand \"{$commandName}\" is not defined.\033[0m\n";
            return 1;
        }

        $commandClass = $this->commands[$commandName];

        /** @var \Fly\Console\Command $command */
        $command = $this->app->make($commandClass);

        return $command->execute(array_slice($argv, 2));
    }

    /**
     * List all available commands.
     */
    protected function listCommands(): void
    {
        echo "\033[32mFly Framework\033[0m v" . $this->app->version() . "\n\n";
        echo "\033[33mUsage:\033[0m\n  command [options] [arguments]\n\n";
        echo "\033[33mAvailable commands:\033[0m\n";

        foreach ($this->commands as $name => $class) {
            /** @var \Fly\Console\Command $cmd */
            $cmd = $this->app->make($class);
            printf("  \033[32m%-20s\033[0m %s\n", $name, $cmd->getDescription());
        }
    }

    /**
     * Terminate the console lifecycle.
     */
    public function terminate(): void
    {
        // Reserved for cleanup
    }
}
