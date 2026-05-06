<?php

declare(strict_types=1);

namespace Fly\Console\Commands;

use Fly\Console\Command;
use Fly\Support\Facades\DB;
use Fly\Application\Application;

class MigrateStatusCommand extends Command
{
    protected string $signature = 'migrate:status';
    protected string $description = 'Show the status of each migration';

    public function __construct(protected Application $app)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $migrationsDir = $this->app->basePath('database/migrations');

        if (!is_dir($migrationsDir)) {
            $this->info('No migrations directory found.');
            return 0;
        }

        // Get ran migrations
        $ran = [];
        try {
            $rows = DB::table('migrations')->get();
            foreach ($rows as $row) {
                $ran[$row->migration] = $row->batch;
            }
        } catch (\Throwable) {
            // migrations table doesn't exist yet
        }

        $files = glob("{$migrationsDir}/*.php");
        sort($files);

        if (empty($files)) {
            $this->info('No migration files found.');
            return 0;
        }

        // Header
        $this->line('');
        printf("  %-10s %-60s %s\n", 'Ran?', 'Migration', 'Batch');
        $this->line(str_repeat('-', 85));

        foreach ($files as $file) {
            $name = basename($file, '.php');
            $isRan = isset($ran[$name]);
            $status = $isRan ? "\033[32mYes\033[0m" : "\033[31mNo\033[0m";
            $batch = $isRan ? (string) $ran[$name] : '';

            printf("  %-19s %-60s %s\n", $status, $name, $batch);
        }

        $this->line('');
        return 0;
    }
}
