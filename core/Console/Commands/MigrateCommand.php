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

        $batchResult = DB::connection()->selectOne("SELECT MAX(batch) as max_batch FROM migrations");
        $batchNumber = ((int) ($batchResult->max_batch ?? 0)) + 1;
        $migrated = false;

        foreach ($files as $file) {
            $fileName = basename($file, '.php');

            if (in_array($fileName, $ranMigrations, true)) {
                continue;
            }

            $this->warning("Migrating: {$fileName}");

            $instance = require $file;

            if (!is_object($instance)) {
                $this->error("Migration file {$fileName} did not return an instance.");
                continue;
            }

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
