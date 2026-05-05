<?php

declare(strict_types=1);

namespace Fly\Console\Commands;

use Fly\Console\Command;

class ServeCommand extends Command
{
    protected string $name = 'serve';
    protected string $description = 'Serve the application on the PHP development server';

    public function handle(): int
    {
        $host = $this->option('host', 'localhost');
        $port = $this->option('port', '8080');

        $this->info("Starting Fly Framework development server:");
        $this->line("http://{$host}:{$port}");
        $this->line("Press Ctrl+C to stop.");

        // Start the built-in PHP server
        passthru(sprintf(
            'php -S %s:%s -t public',
            escapeshellarg((string) $host),
            escapeshellarg((string) $port)
        ));

        return 0;
    }
}
