<?php

declare(strict_types=1);

namespace Fly\Console\Commands;

use Fly\Console\Command;
use Fly\Cache\CacheManager;

class CacheClearCommand extends Command
{
    /**
     * The command signature.
     */
    protected string $signature = 'cache:clear {store?}';

    /**
     * The command description.
     */
    protected string $description = 'Flush the application cache';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $store = $this->argument(0);
        $manager = $this->app->make(CacheManager::class);

        $this->info('Clearing cache...');

        $manager->store($store)->flush();

        $this->info('Application cache cleared!');

        return 0;
    }
}
