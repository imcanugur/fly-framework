<?php

declare(strict_types=1);

namespace Fly\Console\Commands;

use Fly\Console\Command;
use Fly\Support\Facades\DB;
use Fly\Application\Application;

class MigrateFreshCommand extends Command
{
    protected string $signature = 'migrate:fresh';
    protected string $description = 'Drop all tables and re-run all migrations';

    public function __construct(protected Application $app)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->warning('Dropping all tables...');

        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            // Get all tables in SQLite
            $tables = DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
            foreach ($tables as $table) {
                DB::statement("DROP TABLE IF EXISTS {$table->name}");
            }
        } else {
            // MySQL: disable FK checks, drop all
            DB::unprepared('SET FOREIGN_KEY_CHECKS = 0');
            $tables = DB::select('SHOW TABLES');
            foreach ($tables as $table) {
                $tableName = array_values((array) $table)[0];
                DB::statement("DROP TABLE IF EXISTS {$tableName}");
            }
            DB::unprepared('SET FOREIGN_KEY_CHECKS = 1');
        }

        $this->info('Dropped all tables successfully.');
        $this->line('');

        // Re-run migrate
        $migrateCommand = $this->app->make(MigrateCommand::class);
        return $migrateCommand->execute([]);
    }
}
