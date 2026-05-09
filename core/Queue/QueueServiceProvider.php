<?php

declare(strict_types=1);

namespace Fly\Queue;

use Fly\Support\ServiceProvider;

class QueueServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->singleton(QueueManager::class, function ($app) {
            return new QueueManager($app);
        });

        $this->app->alias(QueueManager::class, 'queue');
    }

    /**
     * Boot the service provider.
     *
     * @return void
     */
    public function boot(): void
    {
        //
    }
}
