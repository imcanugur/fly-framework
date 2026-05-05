<?php

declare(strict_types=1);

namespace Fly\Console\Commands;

use Fly\Console\Command;
use Fly\Support\Facades\DB;
use Fly\Support\Facades\Schema;
use Fly\Database\Schema\Blueprint;
use Fly\Application\Application;

class MigrateCommand extends Command
{
    protected string $signature = 'migrate';
    protected string $description = 'Run the database migrations';

    public function __construct(protected Application $app)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        // Ensure migrations table exists
        Schema::create('migrations', function (Blueprint $table) {
            $table->id();
            $table->string('migration');
            $table->integer('batch');
        });

        $ran = DB::table('migrations')->select(['migration'])->get();
        $ranMigrations = array_column($ran, 'migration');

        $migrationsDir = $this->app->basePath('database/migrations');
        if (!is_dir($migrationsDir)) {
            $this->info("Nothing to migrate.");
            return 0;
        }

        $files = glob("{$migrationsDir}/*.php");
        sort($files);

        $batchQuery = DB::table('migrations')->select(['batch'])->limit(1);
        $batchQuery->columns = ['MAX(batch) as max_batch']; // Hack for raw select
        $batchResult = $batchQuery->first();
        $batchNumber = ((int) ($batchResult->max_batch ?? 0)) + 1;
        $migrated = false;

        foreach ($files as $file) {
            $fileName = basename($file, '.php');

            if (in_array($fileName, $ranMigrations, true)) {
                continue;
            }

            $this->warning("Migrating: {$fileName}");

            require_once $file;
            $class = $this->resolveMigrationClass($file);

            if (!class_exists($class)) {
                $this->error("Migration class {$class} not found in file {$fileName}");
                continue;
            }

            $instance = new $class;

            if (method_exists($instance, 'up')) {
                $instance->up();
            }

            DB::table('migrations')->insert([
                'migration' => $fileName,
                'batch'     => $batchNumber,
            ]);

            $this->info("Migrated:  {$fileName}");
            $migrated = true;
        }

        if (!$migrated) {
            $this->info("Nothing to migrate.");
        }

        return 0;
    }

    protected function resolveMigrationClass(string $file): string
    {
        $fileName = basename($file, '.php');
        $parts = explode('_', $fileName);
        
        // Remove timestamp parts (YYYY_MM_DD_HHMMSS)
        if (count($parts) > 4 && is_numeric($parts[0])) {
            array_splice($parts, 0, 4);
        }
        
        return str_replace(' ', '', ucwords(implode(' ', $parts)));
    }
}
