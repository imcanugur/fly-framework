<?php

declare(strict_types=1);

namespace Fly\Auth\Middleware;

use Fly\Http\Request;
use Fly\Http\Response;
use Fly\Auth\AuthManager;
use Closure;

class Authenticate
{
    /**
     * The auth manager instance.
     */
    protected AuthManager $auth;

    /**
     * Create a new middleware instance.
     */
    public function __construct(AuthManager $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ...$guards): Response
    {
        $this->authenticate($request, $guards);

        return $next($request);
    }

    /**
     * Determine if the user is logged in to any of the given guards.
     */
    protected function authenticate(Request $request, array $guards): void
    {
        if (empty($guards)) {
            $guards = [null];
        }

        foreach ($guards as $guard) {
            if ($this->auth->guard($guard)->check()) {
                return;
            }
        }

        $this->unauthenticated($request, $guards);
    }

    /**
     * Handle an unauthenticated user.
     */
    protected function unauthenticated(Request $request, array $guards): void
    {
        throw new \RuntimeException('Unauthenticated.');
    }
}
