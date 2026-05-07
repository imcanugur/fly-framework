<?php

declare(strict_types=1);

namespace Fly\Events;

use Fly\Support\ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected array $listen = [];

    public function register(): void
    {
        $this->app->singleton('events', function ($app) {
            return new Dispatcher($app);
        });
    }

    public function boot(): void
    {
        $events = $this->app->make('events');

        foreach ($this->listen as $event => $listeners) {
            foreach ($listeners as $listener) {
                $events->listen($event, $listener);
            }
        }
    }
}
