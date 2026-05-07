<?php

declare(strict_types=1);

namespace Fly\Kernel;

use Fly\Application\Application;
use Fly\Http\Request;
use Fly\Http\Response;
use Fly\Pipeline\Pipeline;
use Fly\Routing\Router;
use Fly\Http\Middleware\TerminableMiddleware;

/**
 * The HTTP Kernel.
 *
 * Manages the full HTTP lifecycle:
 *   Request Capture → Boot → Pipeline (Global) → Router Pipeline (Route) → Response Emit → Terminate
 */
class HttpKernel
{
    /**
     * Global middleware stack.
     *
     * @var list<string>
     */
    protected array $middleware = [];

    public function __construct(
        protected readonly Application $app,
    ) {}

    /**
     * Handle an incoming HTTP request through the full lifecycle.
     */
    public function handle(): void
    {
        try {
            // 1. Capture
            $request = Request::capture();
            $this->app->instance(Request::class, $request);
            $this->app->instance('request', $request);

            // 2. Boot
            $this->app->boot();

            // 3. Dispatch through global middleware and router
            $response = $this->sendRequestThroughRouter($request);
        } catch (\Throwable $e) {
            $response = $this->reportAndRender($request ?? Request::capture(), $e);
        }

        // 4. Emit
        $response->send();

        // 5. Terminate
        $this->terminate($request ?? Request::capture(), $response);
    }

    /**
     * Report and render the exception.
     */
    protected function reportAndRender(Request $request, \Throwable $e): Response
    {
        $handler = $this->app->make(\Fly\Exceptions\Handler::class);
        
        $handler->report($e);
        
        return $handler->render($request, $e);
    }

    /**
     * Send the request through the global middleware pipeline to the router.
     */
    protected function sendRequestThroughRouter(Request $request): Response
    {
        return (new Pipeline($this->app))
            ->send($request)
            ->through($this->middleware)
            ->then(fn (Request $req) => $this->dispatchToRouter($req));
    }

    /**
     * Dispatch the request to the router.
     */
    protected function dispatchToRouter(Request $request): Response
    {
        /** @var Router $router */
        $router = $this->app->make(Router::class);
        return $router->dispatch($request);
    }

    /**
     * Post-response cleanup and terminable middleware execution.
     */
    protected function terminate(Request $request, Response $response): void
    {
        $middlewares = $this->middleware;

        /** @var Router $router */
        $router = $this->app->make(Router::class);
        $route = $router->current();

        if ($route !== null) {
            $middlewares = array_merge($middlewares, $route->getMiddleware());
        }

        foreach ($middlewares as $middleware) {
            $instance = $this->app->make($middleware);

            if ($instance instanceof TerminableMiddleware) {
                $instance->terminate($request, $response);
            }
        }
    }
}
