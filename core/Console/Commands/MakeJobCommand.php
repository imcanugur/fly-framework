<?php

declare(strict_types=1);

namespace Fly\Console\Commands;

use Fly\Console\GeneratorCommand;

class MakeJobCommand extends GeneratorCommand
{
    /**
     * The command signature.
     *
     * @var string
     */
    protected string $signature = 'make:job {name}';

    /**
     * The command description.
     *
     * @var string
     */
    protected string $description = 'Create a new job class';

    /**
     * Get the stub name for the generator.
     *
     * @return string
     */
    protected function getStubName(): string
    {
        return 'job.stub';
    }

    /**
     * Get the destination file path.
     *
     * @param string $name
     * @return string
     */
    protected function getPath(string $name): string
    {
        return $this->app->appPath('Jobs/' . $name . '.php');
    }

    /**
     * Get the default namespace for the class.
     *
     * @return string
     */
    protected function getDefaultNamespace(): string
    {
        return 'App\Jobs';
    }
}
