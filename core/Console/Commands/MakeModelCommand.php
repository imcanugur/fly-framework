<?php

declare(strict_types=1);

namespace Fly\Console\Commands;

use Fly\Console\GeneratorCommand;
use Fly\Application\Application;

class MakeModelCommand extends GeneratorCommand
{
    protected string $name = 'make:model';
    protected string $description = 'Create a new model class';

    public function __construct(protected readonly Application $app) {}

    protected function getStub(): string
    {
        return <<<EOF
<?php

declare(strict_types=1);

namespace {{ namespace }};

class {{ class }}
{
    // Phase 9 ORM Base Model will be extended here later
}

EOF;
    }

    protected function getDefaultNamespace(): string
    {
        return 'App\\Models';
    }

    protected function getPath(string $name): string
    {
        return $this->app->appPath('Models/' . str_replace('\\', '/', $name) . '.php');
    }
}
