<?php

declare(strict_types=1);

namespace Fly\Routing;

use Closure;
use Fly\Http\Request;
use Fly\Http\Response;
use RuntimeException;

/**
 * The core routing engine.
 *
 * Maintains an internal registry of route definitions
 * and dispatches incoming requests to the matched action.
 */
class Router
{
    /**
     * The route registry, keyed by HTTP method.
     *
     * @var array<string, array<string, array{pattern: string, action: Closure|array}>>
     */
    protected array $routes = [];

    /**
     * Register a GET route.
     */
    public function get(string $uri, Closure|array $action): void
    {
        $this->addRoute('GET', $uri, $action);
    }

    /**
     * Register a POST route.
     */
    public function post(string $uri, Closure|array $action): void
    {
        $this->addRoute('POST', $uri, $action);
    }

    /**
     * Register a PUT route.
     */
    public function put(string $uri, Closure|array $action): void
    {
        $this->addRoute('PUT', $uri, $action);
    }

    /**
     * Register a PATCH route.
     */
    public function patch(string $uri, Closure|array $action): void
    {
        $this->addRoute('PATCH', $uri, $action);
    }

    /**
     * Register a DELETE route.
     */
    public function delete(string $uri, Closure|array $action): void
    {
        $this->addRoute('DELETE', $uri, $action);
    }

    /**
     * Add a route to the internal registry.
     */
    protected function addRoute(string $method, string $uri, Closure|array $action): void
    {
        $uri = '/' . trim($uri, '/');

        $this->routes[$method][$uri] = [
            'pattern' => $this->compilePattern($uri),
            'action'  => $action,
        ];
    }

    /**
     * Dispatch the given request and return a Response.
     */
    public function dispatch(Request $request): Response
    {
        $method = $request->method();
        $path   = '/' . trim($request->path(), '/');

        if (!isset($this->routes[$method])) {
            return $this->createNotFoundResponse($path);
        }

        foreach ($this->routes[$method] as $uri => $route) {
            if (preg_match($route['pattern'], $path, $matches)) {
                // Extract only named parameters
                $params = array_filter(
                    $matches,
                    fn(string $key) => !is_numeric($key),
                    ARRAY_FILTER_USE_KEY,
                );

                return $this->callAction($route['action'], $params, $request);
            }
        }

        return $this->createNotFoundResponse($path);
    }

    /**
     * Get all registered routes (for debugging / cache compilation).
     *
     * @return array<string, array>
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    // ----------------------------------------------------------------
    // Internals
    // ----------------------------------------------------------------

    /**
     * Compile a URI pattern (e.g., /users/{id}) into a regex.
     */
    protected function compilePattern(string $uri): string
    {
        // Escape slashes, then replace {param} with named capture groups
        $pattern = preg_replace(
            '/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/',
            '(?P<$1>[^/]+)',
            $uri,
        );

        return '#^' . $pattern . '$#';
    }

    /**
     * Call the matched route action and normalize the return to a Response.
     *
     * @param array<string, string> $params
     */
    protected function callAction(Closure|array $action, array $params, Request $request): Response
    {
        if ($action instanceof Closure) {
            $result = $action($request, ...$params);
        } else {
            // Array action: [ControllerClass, method]
            [$controller, $method] = $action;

            if (is_string($controller)) {
                $controller = new $controller();
            }

            $result = $controller->{$method}($request, ...$params);
        }

        return $this->normalizeResponse($result);
    }

    /**
     * Convert the action's return value into a proper Response.
     */
    protected function normalizeResponse(mixed $result): Response
    {
        if ($result instanceof Response) {
            return $result;
        }

        if (is_string($result)) {
            return Response::make($result);
        }

        if (is_array($result) || is_object($result)) {
            return Response::json($result);
        }

        return Response::make((string) $result);
    }

    /**
     * Create a 404 Not Found response.
     */
    protected function createNotFoundResponse(string $path): Response
    {
        return Response::make("404 Not Found: {$path}", 404);
    }
}
