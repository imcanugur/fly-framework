<?php

declare(strict_types=1);

if (!function_exists('env')) {
    /**
     * Gets the value of an environment variable.
     */
    function env(string $key, mixed $default = null): mixed
    {
        return \Fly\Support\Env::get($key, $default);
    }
}

if (!function_exists('config')) {
    /**
     * Get / set the specified configuration value.
     *
     * If an array is passed as the key, we will assume you want to set an array of values.
     */
    function config(string|array|null $key = null, mixed $default = null): mixed
    {
        $container = \Fly\Container\Container::getInstance();

        if ($container === null) {
            return $default;
        }

        $repository = $container->make('config');

        if ($key === null) {
            return $repository;
        }

        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $repository->set($k, $v);
            }
            return null;
        }

        return $repository->get($key, $default);
    }
}

if (!function_exists('database_path')) {
    /**
     * Get the path to the database directory.
     */
    function database_path(string $path = ''): string
    {
        $app = \Fly\Container\Container::getInstance()->make('app');
        return $app->basePath('database' . ($path ? '/' . ltrim($path, '/') : ''));
    }
}

if (!function_exists('view')) {
    /**
     * Get the evaluated view contents for the given view.
     */
    function view(string $view = null, array $data = []): \Fly\View\Factory|\Fly\View\View
    {
        $factory = \Fly\Container\Container::getInstance()->make('view');

        if (func_num_args() === 0) {
            return $factory;
        }

        return $factory->make($view, $data);
    }
}
if (!function_exists('csrf_token')) {
    /**
     * Get the CSRF token value.
     */
    function csrf_token(): string
    {
        return 'fly-mock-token-' . bin2hex(random_bytes(16));
    }
}

if (!function_exists('dd')) {
    /**
     * Die and Dump.
     */
    function dd(...$vars): void
    {
        if (PHP_SAPI === 'cli') {
            foreach ($vars as $v) {
                var_dump($v);
            }
            die(1);
        }

        // Web Dump
        echo '<!DOCTYPE html><html lang="en"><head><title>Fly Dump</title>';
        echo '<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;700&family=JetBrains+Mono&display=swap" rel="stylesheet">';
        echo '<style>
            body { background: #ffffff; color: #0f172a; font-family: "Outfit", sans-serif; padding: 64px; }
            .dark-theme body { background: #0f172a; color: #f8fafc; }
            .header { display: flex; align-items: center; gap: 12px; font-weight: 700; font-size: 16px; margin-bottom: 40px; border-bottom: 1px solid #f1f5f9; padding-bottom: 20px; }
            .dark-theme .header { border-bottom-color: #334155; }
            pre { background: #f8fafc; border: 1px solid #f1f5f9; padding: 32px; border-radius: 16px; font-family: "JetBrains Mono", monospace; font-size: 14px; overflow-x: auto; margin-bottom: 24px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); }
            .dark-theme pre { background: #1e293b; border-color: #334155; color: #e2e8f0; }
            .tag { background: #4f46e5; color: #fff; padding: 4px 10px; border-radius: 6px; font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; }
        </style></head><body>';
        echo '<div class="header"><span class="tag">DUMP</span> FLY FRAMEWORK</div>';
        
        foreach ($vars as $v) {
            echo '<pre>';
            ob_start();
            var_dump($v);
            $dump = ob_get_clean();
            echo htmlspecialchars($dump);
            echo '</pre>';
        }
        
        echo '</body></html>';
        die(1);
    }
}

if (!function_exists('app')) {
    /**
     * Get the available container instance.
     */
    function app(string $abstract = null, array $parameters = []): mixed
    {
        if (is_null($abstract)) {
            return \Fly\Container\Container::getInstance();
        }

        return \Fly\Container\Container::getInstance()->make($abstract, $parameters);
    }
}

if (!function_exists('event')) {
    /**
     * Dispatch an event and call the listeners.
     */
    function event(string|object $event, mixed $payload = []): array
    {
        return app('events')->dispatch($event, $payload);
    }
}

if (!function_exists('fly')) {
    /**
     * Fly Framework Branding Helper
     */
    function fly(): string
    {
        return '🚀 Fly Framework';
    }
}
