<?php

declare(strict_types=1);

namespace Fly\Http\Middleware;

use Fly\Http\Request;
use Fly\Http\Response;

/**
 * Interface for Terminable Middleware.
 *
 * Terminable middleware executes logic after the HTTP response
 * has been sent to the browser (e.g., saving sessions, closing connections).
 */
interface TerminableMiddleware
{
    /**
     * Terminate the request lifecycle.
     */
    public function terminate(Request $request, Response $response): void;
}
