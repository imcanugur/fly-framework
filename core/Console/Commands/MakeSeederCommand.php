<?php

declare(strict_types=1);

namespace Fly\Console\Commands;

use Fly\Console\GeneratorCommand;

class MakeSeederCommand extends GeneratorCommand
{
    protected string $name = 'make:seeder';
    protected string $description = 'Create a new database seeder class';

    protected function getStubName(): string
    {
        return 'seeder.stub';
    }

    protected function getDefaultNamespace(): string
    {
        return 'App\\Database\\Seeders';
    }

    protected function getPath(string $name): string
    {
        return $this->app->basePath('app/Database/Seeders/' . $name . '.php');
    }
}
