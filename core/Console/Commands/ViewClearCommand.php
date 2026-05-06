<?php

declare(strict_types=1);

namespace Fly\Console\Commands;

use Fly\Console\Command;
use Fly\Container\Container;

class ViewClearCommand extends Command
{
    protected string $name = 'view:clear';
    protected string $description = 'Clear all compiled view files';

    public function handle(): int
    {
        $path = Container::getInstance()->make('app')->basePath('storage/framework/views');
        
        if (!is_dir($path)) {
            $this->info('View cache directory does not exist.');
            return 0;
        }

        $directory = new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS);
        $iterator = new \RecursiveIteratorIterator($directory, \RecursiveIteratorIterator::CHILD_FIRST);

        $count = 0;
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                unlink($file->getPathname());
                $count++;
            }
        }

        $this->info("Successfully cleared {$count} compiled views!");
        
        return 0;
    }
}
