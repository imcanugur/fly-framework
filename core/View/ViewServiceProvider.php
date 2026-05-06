<?php

declare(strict_types=1);

namespace Fly\View;

use Fly\Support\ServiceProvider;

class ViewServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('view', function ($app) {
            $viewPath = $app->basePath('resources/views');
            $cachePath = $app->basePath('storage/framework/views');
            
            return new Factory($viewPath, $cachePath);
        });
    }

    public function boot(): void
    {
        // View engine is ready
    }
}
