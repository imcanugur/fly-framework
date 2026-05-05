<?php

declare(strict_types=1);

namespace Fly\Console\Commands;

use Fly\Console\GeneratorCommand;
use Fly\Application\Application;

class MakeControllerCommand extends GeneratorCommand
{
    protected string $name = 'make:controller';
    protected string $description = 'Create a new controller class';

    public function __construct(protected readonly Application $app) {}

    protected function getStub(): string
    {
        return <<<EOF
<?php

declare(strict_types=1);

namespace {{ namespace }};

use Fly\Http\Request;
use Fly\Http\Response;

class {{ class }}
{
    public function index(Request \$request): Response
    {
        // return Response::json(['message' => 'Hello World']);
    }
}

EOF;
    }

    protected function getDefaultNamespace(): string
    {
        return 'App\\Http\\Controllers';
    }

    protected function getPath(string $name): string
    {
        return $this->app->appPath('Http/Controllers/' . str_replace('\\', '/', $name) . '.php');
    }
}
