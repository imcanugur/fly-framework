<?php

declare(strict_types=1);

namespace App\Providers;

use Fly\Support\ServiceProvider;

/**
 * Main application service provider.
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register your application-specific services here
        // $this->app->singleton(MyService::class, fn () => new MyService());
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Load web routes
        $routesPath = $this->app->routesPath('web.php');
        if (file_exists($routesPath)) {
            require $routesPath;
        }

        // View Composer Demo
        view()->composer('welcome', function ($view) {
            $view->with('composer_message', 'This message was injected by a View Composer! 🎻');
        });

        // Custom Directive Demo
        view()->directive('upper', function ($expression) {
            return "<?php echo strtoupper($expression); ?>";
        });
    }
}
