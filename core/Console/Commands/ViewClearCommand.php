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

        $files = glob($path . '/*.php');
        
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        $this->info('Compiled views cleared successfully!');
        
        return 0;
    }
}
