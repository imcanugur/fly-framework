<?php

declare(strict_types=1);

namespace App\Middleware;

use Closure;
use Fly\Http\Request;
use Fly\Http\Response;
use Fly\Http\Middleware\MiddlewareInterface;
use Fly\Http\Middleware\TerminableMiddleware;

/**
 * Example application middleware demonstrating before/after operations
 * and terminable cleanup.
 */
class ExampleMiddleware implements MiddlewareInterface, TerminableMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Before action
        $startTime = microtime(true);

        // 2. Delegate to next pipe
        /** @var Response $response */
        $response = $next($request);

        // 3. After action
        $duration = microtime(true) - $startTime;
        $response->setHeader('X-Response-Time', sprintf('%2.2fms', $duration * 1000));

        return $response;
    }

    public function terminate(Request $request, Response $response): void
    {
        // 4. Terminable action (runs after response is sent to browser)
        error_log(sprintf(
            "TerminableMiddleware: Request to %s completed with status %d.",
            $request->path(),
            $response->getStatusCode()
        ));
    }
}
