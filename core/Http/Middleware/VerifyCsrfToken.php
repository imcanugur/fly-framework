<?php

declare(strict_types=1);

namespace Fly\Http\Middleware;

use Fly\Http\Request;
use Fly\Http\Response;
use Fly\Support\Str;
use Closure;

class VerifyCsrfToken implements MiddlewareInterface
{
    /**
     * The URIs that should be excluded from CSRF verification.
     */
    protected array $except = [];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->isReading($request) || $this->inExceptArray($request) || $this->tokensMatch($request)) {
            return $next($request);
        }

        throw new \RuntimeException('CSRF token mismatch.');
    }

    /**
     * Determine if the HTTP request uses a "reading" verb.
     */
    protected function isReading(Request $request): bool
    {
        return in_array($request->method(), ['GET', 'HEAD', 'OPTIONS']);
    }

    /**
     * Determine if the request has a URI that should pass through CSRF verification.
     */
    protected function inExceptArray(Request $request): bool
    {
        foreach ($this->except as $except) {
            if ($except !== '/') {
                $except = trim($except, '/');
            }

            if ($request->is($except)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if the session and input CSRF tokens match.
     */
    protected function tokensMatch(Request $request): bool
    {
        $token = $this->getTokenFromRequest($request);

        return is_string($request->session()->get('_token')) &&
               is_string($token) &&
               hash_equals($request->session()->get('_token'), $token);
    }

    /**
     * Get the CSRF token from the request.
     */
    protected function getTokenFromRequest(Request $request): ?string
    {
        $token = $request->input('_token') ?: $request->header('X-CSRF-TOKEN');

        if (!$token && $header = $request->header('X-XSRF-TOKEN')) {
            $token = $header; // In a real framework, we would decrypt this
        }

        return $token;
    }
}
