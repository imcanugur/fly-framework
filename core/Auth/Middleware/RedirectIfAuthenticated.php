<?php

declare(strict_types=1);

namespace Fly\Auth\Middleware;

use Fly\Http\Request;
use Fly\Http\Response;
use Fly\Auth\AuthManager;
use Closure;

class RedirectIfAuthenticated
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
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            if ($this->auth->guard($guard)->check()) {
                return Response::redirect('/home');
            }
        }

        return $next($request);
    }
}
