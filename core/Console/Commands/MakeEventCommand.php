<?php

declare(strict_types=1);

namespace Fly\Console\Commands;

use Fly\Console\GeneratorCommand;
use Fly\Application\Application;

class MakeEventCommand extends GeneratorCommand
{
    protected string $name = 'make:event';
    protected string $description = 'Create a new event class';

    public function __construct(protected readonly Application $app) {}

    protected function getStubName(): string
    {
        return 'event.stub';
    }

    protected function getDefaultNamespace(): string
    {
        return 'App\\Events';
    }

    protected function getPath(string $name): string
    {
        return $this->app->basePath('app/Events/' . str_replace('\\', '/', $name) . '.php');
    }
}
