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
        echo '<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;700;800&family=JetBrains+Mono&display=swap" rel="stylesheet">';
        echo '<script src="https://unpkg.com/lucide@latest"></script>';
        echo '<style>
            :root { --bg: #ffffff; --text: #0f172a; --muted: #64748b; --border: #f1f5f9; --accent: #4f46e5; --code-bg: #fafafa; }
            .dark-mode { --bg: #0f172a; --text: #f8fafc; --muted: #94a3b8; --border: #1e293b; --accent: #818cf8; --code-bg: #1e293b; }
            body { background: var(--bg); color: var(--text); font-family: "Outfit", sans-serif; padding: 80px 120px; transition: all 0.3s; }
            .header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 60px; border-bottom: 1px solid var(--border); padding-bottom: 24px; }
            .logo { display: flex; align-items: center; gap: 8px; font-weight: 800; font-size: 14px; color: var(--muted); letter-spacing: 0.1em; }
            .logo span { color: var(--accent); }
            pre { background: var(--code-bg); border: 1px solid var(--border); padding: 40px; border-radius: 24px; font-family: "JetBrains Mono", monospace; font-size: 15px; overflow-x: auto; margin-bottom: 32px; box-shadow: 0 4px 20px rgba(0,0,0,0.02); color: var(--text); }
            .fab { position: fixed; bottom: 40px; right: 40px; background: var(--text); color: var(--bg); width: 56px; height: 56px; border-radius: 18px; display: flex; align-items: center; justify-content: center; cursor: pointer; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); }
        </style></head><body>';
        echo '<div class="header"><div class="logo">FLY <span>DUMP</span></div><div style="font-size: 11px; font-weight: 800; color: var(--muted); letter-spacing: 0.1em;">' . date('H:i:s') . '</div></div>';
        
        foreach ($vars as $v) {
            echo '<pre>';
            ob_start();
            var_dump($v);
            $dump = ob_get_clean();
            echo htmlspecialchars($dump);
            echo '</pre>';
        }
        
        echo '<div class="fab" onclick="document.body.classList.toggle(\'dark-mode\')"><i data-lucide="sun-moon"></i></div>';
        echo '<script>lucide.createIcons();</script></body></html>';
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

if (!function_exists('cache')) {
    /**
     * Get / set the specified cache value.
     */
    function cache(string|array|null $key = null, mixed $default = null): mixed
    {
        $manager = app('cache');

        if (is_null($key)) {
            return $manager;
        }

        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $manager->put($k, $v);
            }
            return null;
        }

        return $manager->get($key, $default);
    }
}

if (!function_exists('dispatch')) {
    /**
     * Dispatch a job to the queue.
     */
    function dispatch(object|callable $job): mixed
    {
        if ($job instanceof \Closure) {
            $job = new \Fly\Queue\Jobs\ClosureJob($job);
        }

        return app('queue')->push($job);
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
