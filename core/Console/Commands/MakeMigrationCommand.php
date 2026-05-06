<?php

declare(strict_types=1);

namespace Fly\Console\Commands;

use Fly\Console\GeneratorCommand;
use Fly\Application\Application;

class MakeMigrationCommand extends GeneratorCommand
{
    protected string $name = 'make:migration';
    protected string $description = 'Create a new database migration class';

    public function __construct(protected readonly Application $app) {}

    protected function getStubName(): string
    {
        return 'migration.stub';
    }

    protected function getDefaultNamespace(): string
    {
        return '';
    }

    protected function getPath(string $name): string
    {
        $timestamp = date('Y_m_d_His');
        $fileName = $timestamp . '_' . strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $name)) . '.php';

        return $this->app->basePath('database/migrations/' . $fileName);
    }

    protected function buildClass(string $name): string
    {
        $stub = parent::buildClass($name);
        $snakeName = \Fly\Support\Str::snake($name);
        
        // Extract table name: create_users_table → users
        if (preg_match('/^create_(.+?)(?:_table)?$/', $snakeName, $m)) {
            $table = $m[1];
        } else {
            $table = $snakeName;
        }
        
        return str_replace('{{ table }}', $table, $stub);
    }
}
