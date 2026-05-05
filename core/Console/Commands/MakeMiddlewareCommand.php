<?php

declare(strict_types=1);

namespace Fly\Console\Commands;

use Fly\Console\GeneratorCommand;
use Fly\Application\Application;

class MakeMiddlewareCommand extends GeneratorCommand
{
    protected string $name = 'make:middleware';
    protected string $description = 'Create a new middleware class';

    public function __construct(protected readonly Application $app) {}

    protected function getStubName(): string
    {
        return 'middleware.stub';
    }

    protected function getDefaultNamespace(): string
    {
        return 'App\\Middleware';
    }

    protected function getPath(string $name): string
    {
        return $this->app->appPath('Middleware/' . str_replace('\\', '/', $name) . '.php');
    }
}
