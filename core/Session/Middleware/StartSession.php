<?php

declare(strict_types=1);

namespace Fly\Session\Middleware;

use Fly\Http\Request;
use Fly\Http\Response;
use Fly\Session\SessionManager;
use Fly\Session\Store;
use Closure;

class StartSession
{
    /**
     * The session manager instance.
     */
    protected SessionManager $manager;

    /**
     * Create a new middleware instance.
     */
    public function __construct(SessionManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $session = $this->startSession($request);

        $request->setSession($session);

        $response = $next($request);

        $this->storeCurrentUrl($request, $session);

        $this->addCookieToResponse($response, $session);

        $session->save();

        return $response;
    }

    /**
     * Start the session for the given request.
     */
    protected function startSession(Request $request): Store
    {
        $session = $this->manager->driver();

        $session->setId($request->cookies->get($session->getName()));

        $session->start();

        if (!$session->has('_token')) {
            $session->put('_token', \Fly\Support\Str::random(40));
        }

        return $session;
    }

    /**
     * Store the current URL in the session.
     */
    protected function storeCurrentUrl(Request $request, Store $session): void
    {
        if ($request->isMethod('GET') && !$request->isAjax()) {
            $session->put('_previous.url', $request->fullUrl());
        }
    }

    /**
     * Add the session cookie to the response.
     */
    protected function addCookieToResponse(Response $response, Store $session): void
    {
        $config = app('config')->get('session');

        $response->withCookie(
            $session->getName(),
            $session->getId(),
            $config['lifetime'] * 60,
            $config['path'],
            $config['domain'],
            $config['secure'],
            $config['http_only']
        );
    }
}
