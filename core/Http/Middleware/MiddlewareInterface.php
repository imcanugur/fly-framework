<?php

declare(strict_types=1);

namespace Fly\Http\Middleware;

use Fly\Http\Request;
use Fly\Http\Response;
use Closure;

/**
 * Interface for HTTP Middleware.
 */
interface MiddlewareInterface
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure(Request): Response $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response;
}
