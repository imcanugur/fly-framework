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

    protected function getStub(): string
    {
        return <<<EOF
<?php

declare(strict_types=1);

namespace {{ namespace }};

use Closure;
use Fly\Http\Request;
use Fly\Http\Response;
use Fly\Http\Middleware\MiddlewareInterface;

class {{ class }} implements MiddlewareInterface
{
    public function handle(Request \$request, Closure \$next): Response
    {
        // Before action

        /** @var Response \$response */
        \$response = \$next(\$request);

        // After action

        return \$response;
    }
}

EOF;
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
