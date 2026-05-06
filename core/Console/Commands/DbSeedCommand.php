<?php

declare(strict_types=1);

namespace Fly\Console\Commands;

use Fly\Console\Command;
use Fly\Application\Application;

class DbSeedCommand extends Command
{
    protected string $signature = 'db:seed {--class=DatabaseSeeder : The class name of the root seeder}';
    protected string $description = 'Seed the database with records';

    public function __construct(protected Application $app)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $className = $this->option('class') ?: 'DatabaseSeeder';
        $fullClass = '\\App\\Database\\Seeders\\' . $className;

        if (!class_exists($fullClass)) {
            $this->error("Seeder class [{$fullClass}] does not exist.");
            return 1;
        }

        $this->info("Seeding database...");

        $seeder = new $fullClass;
        $seeder->run();

        $this->info("Database seeding completed successfully.");
        return 0;
    }
}
