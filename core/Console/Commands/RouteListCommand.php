<?php

declare(strict_types=1);

namespace Fly\Console\Commands;

use Fly\Console\Command;
use Fly\Routing\Router;
use Fly\Application\Application;

class RouteListCommand extends Command
{
    protected string $name = 'route:list';
    protected string $description = 'List all registered routes';

    public function __construct(protected readonly Application $app) {}

    public function handle(): int
    {
        /** @var Router $router */
        $router = $this->app->make(Router::class);
        $routes = $router->getRoutes();

        if (empty($routes)) {
            $this->warning("Your application doesn't have any routes.");
            return 0;
        }

        $this->line(str_pad('METHOD', 10) . str_pad('URI', 40) . str_pad('NAME', 25) . 'ACTION');
        $this->line(str_repeat('-', 95));

        foreach ($routes as $method => $entries) {
            foreach ($entries as $route) {
                $action = $route->getAction();
                $actionStr = is_array($action) ? $action[0] . '@' . $action[1] : 'Closure';
                $name = $route->getName() ?? '';
                
                $this->line(
                    str_pad($method, 10) . 
                    str_pad($route->getUri(), 40) . 
                    str_pad($name, 25) . 
                    $actionStr
                );
            }
        }

        return 0;
    }
}
