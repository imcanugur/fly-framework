<?php

declare(strict_types=1);

namespace Fly\Kernel;

use Fly\Application\Application;
use Fly\Http\Request;
use Fly\Http\Response;
use Fly\Routing\Router;

/**
 * The HTTP Kernel.
 *
 * Manages the full HTTP lifecycle:
 *   Request Capture → Boot → Router Dispatch → Response Emit → Terminate
 */
class HttpKernel
{
    public function __construct(
        protected readonly Application $app,
    ) {}

    /**
     * Handle an incoming HTTP request through the full lifecycle.
     */
    public function handle(): void
    {
        // 1. Capture
        $request = Request::capture();
        $this->app->instance(Request::class, $request);
        $this->app->instance('request', $request);

        // 2. Boot
        $this->app->boot();

        // 3. Dispatch
        $response = $this->dispatchToRouter($request);

        // 4. Emit
        $response->send();

        // 5. Terminate
        $this->terminate($request, $response);
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
     * Post-response cleanup (terminable middleware in Phase 5).
     */
    protected function terminate(Request $request, Response $response): void
    {
        // Reserved for Phase 5
    }
}
