<?php

declare(strict_types=1);

namespace Fly\Console\Commands;

use Fly\Console\GeneratorCommand;
use Fly\Application\Application;

class MakeProviderCommand extends GeneratorCommand
{
    protected string $name = 'make:provider';
    protected string $description = 'Create a new service provider class';

    public function __construct(protected readonly Application $app) {}

    protected function getStubName(): string
    {
        return 'provider.stub';
    }

    protected function getDefaultNamespace(): string
    {
        return 'App\\Providers';
    }

    protected function getPath(string $name): string
    {
        return $this->app->appPath('Providers/' . str_replace('\\', '/', $name) . '.php');
    }
}
