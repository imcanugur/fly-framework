<?php

declare(strict_types=1);

namespace Fly\Console\Commands;

use Fly\Console\Command;
use Fly\Support\Facades\DB;
use Fly\Application\Application;

class MigrateRollbackCommand extends Command
{
    protected string $signature = 'migrate:rollback {--step=1 : The number of batches to rollback}';
    protected string $description = 'Rollback the last database migration batch';

    public function __construct(protected Application $app)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $steps = (int) ($this->option('step') ?: 1);
        $migrationsDir = $this->app->basePath('database/migrations');

        // Get latest batch
        $batchResult = DB::table('migrations')
            ->select('MAX(batch) as max_batch')
            ->first();

        $latestBatch = (int) ($batchResult->max_batch ?? 0);

        if ($latestBatch === 0) {
            $this->info('Nothing to rollback.');
            return 0;
        }

        $targetBatch = max(1, $latestBatch - $steps + 1);
        $rolled = false;

        for ($batch = $latestBatch; $batch >= $targetBatch; $batch--) {
            $migrations = DB::table('migrations')
                ->where('batch', (string) $batch)
                ->orderBy('id', 'desc')
                ->get();

            foreach ($migrations as $migration) {
                $file = $migrationsDir . '/' . $migration->migration . '.php';

                if (!file_exists($file)) {
                    $this->error("Migration file not found: {$migration->migration}");
                    continue;
                }

                $this->warning("Rolling back: {$migration->migration}");

                $instance = require $file;

                if (is_object($instance) && method_exists($instance, 'down')) {
                    $instance->down();
                }

                DB::table('migrations')
                    ->where('migration', $migration->migration)
                    ->delete();

                $this->info("Rolled back:  {$migration->migration}");
                $rolled = true;
            }
        }

        if (!$rolled) {
            $this->info('Nothing to rollback.');
        }

        return 0;
    }
}
