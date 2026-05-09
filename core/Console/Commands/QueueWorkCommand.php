<?php

declare(strict_types=1);

namespace Fly\Console\Commands;

use Fly\Console\Command;
use Fly\Queue\Worker;
use Fly\Queue\QueueManager;

class QueueWorkCommand extends Command
{
    /**
     * The command signature.
     *
     * @var string
     */
    protected string $signature = 'queue:work {--connection=} {--queue=} {--sleep=3}';

    /**
     * The command description.
     *
     * @var string
     */
    protected string $description = 'Start processing jobs on the queue as a worker';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $this->info('Fly Queue Worker is running...');
        $this->comment('Press Ctrl+C to stop.');

        $connection = $this->option('connection');
        $queue = $this->option('queue');
        $sleep = (int) $this->option('sleep');

        $worker = new Worker(
            $this->app,
            $this->app->make(QueueManager::class)
        );

        $worker->run($connection, $queue, $sleep);

        return 0;
    }
}
