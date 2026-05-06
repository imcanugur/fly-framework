<?php

declare(strict_types=1);

namespace Fly\Console\Commands;

use Fly\Console\Command;
use Fly\Container\Container;

class ViewCacheCommand extends Command
{
    protected string $name = 'view:cache';
    protected string $description = 'Compile all application views';

    public function handle(): int
    {
        $this->info('Compiling views...');

        $factory = Container::getInstance()->make('view');
        $viewPath = Container::getInstance()->make('app')->basePath('resources/views');

        if (!is_dir($viewPath)) {
            $this->error('View directory not found: ' . $viewPath);
            return 1;
        }

        $this->compileViews($factory, $viewPath);

        $this->info('All views compiled successfully!');
        
        return 0;
    }

    protected function compileViews($factory, $basePath): void
    {
        $directory = new \RecursiveDirectoryIterator($basePath);
        $iterator = new \RecursiveIteratorIterator($directory);

        foreach ($iterator as $file) {
            if ($file->isFile() && str_ends_with($file->getFilename(), '.fly.php')) {
                $pathname = $file->getPathname();
                $relative = str_replace([$basePath . '/', '.fly.php'], '', $pathname);
                $viewName = str_replace('/', '.', $relative);

                try {
                    $factory->precompile($viewName);
                    $this->line("  Compiled: <info>{$viewName}</info>");
                } catch (\Throwable $e) {
                    $this->error("  Failed to compile [{$viewName}]: " . $e->getMessage());
                }
            }
        }
    }
}
