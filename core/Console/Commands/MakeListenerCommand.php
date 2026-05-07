<?php

declare(strict_types=1);

namespace Fly\Console\Commands;

use Fly\Console\GeneratorCommand;
use Fly\Application\Application;

class MakeListenerCommand extends GeneratorCommand
{
    protected string $name = 'make:listener';
    protected string $description = 'Create a new event listener class';

    public function __construct(protected readonly Application $app) {}

    protected function getStubName(): string
    {
        return 'listener.stub';
    }

    protected function getDefaultNamespace(): string
    {
        return 'App\\Listeners';
    }

    protected function getPath(string $name): string
    {
        return $this->app->basePath('app/Listeners/' . str_replace('\\', '/', $name) . '.php');
    }
}
