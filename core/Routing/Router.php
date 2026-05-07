<?php

declare(strict_types=1);

namespace Fly\Routing;

use Closure;
use Fly\Http\Request;
use Fly\Http\Response;
use Fly\Pipeline\Pipeline;

/**
 * The core routing engine.
 *
 * Phase 3: Full-featured router with route groups, named routes,
 * parameter constraints, optional params, 405 detection,
 * controller resolution via container, and route caching.
 */
class Router
{
    /**
     * Route registry keyed by HTTP method.
     *
     * @var array<string, list<RouteEntry>>
     */
    protected array $routes = [];

    /**
     * Named route lookup table.
     *
     * @var array<string, RouteEntry>
     */
    protected array $namedRoutes = [];

    /**
     * Route group manager.
     */
    protected RouteGroup $group;

    /**
     * All unique registered URIs (for 405 detection).
     *
     * @var array<string, list<string>>
     */
    protected array $uriMethodMap = [];

    /**
     * The currently dispatched route.
     */
    protected ?RouteEntry $currentRoute = null;

    public function __construct()
    {
        $this->group = new RouteGroup();
    }

    // ----------------------------------------------------------------
    // Registration
    // ----------------------------------------------------------------

    public function get(string $uri, Closure|array $action): RouteEntry
    {
        return $this->addRoute('GET', $uri, $action);
    }

    public function post(string $uri, Closure|array $action): RouteEntry
    {
        return $this->addRoute('POST', $uri, $action);
    }

    public function put(string $uri, Closure|array $action): RouteEntry
    {
        return $this->addRoute('PUT', $uri, $action);
    }

    public function patch(string $uri, Closure|array $action): RouteEntry
    {
        return $this->addRoute('PATCH', $uri, $action);
    }

    public function delete(string $uri, Closure|array $action): RouteEntry
    {
        return $this->addRoute('DELETE', $uri, $action);
    }

    /**
     * Register a route that responds to any HTTP method.
     */
    public function any(string $uri, Closure|array $action): RouteEntry
    {
        $entry = null;
        foreach (['GET', 'POST', 'PUT', 'PATCH', 'DELETE'] as $method) {
            $entry = $this->addRoute($method, $uri, $action);
        }
        return $entry;
    }

    /**
     * Register a route that responds to multiple HTTP methods.
     *
     * @param list<string> $methods
     */
    public function match(array $methods, string $uri, Closure|array $action): RouteEntry
    {
        $entry = null;
        foreach ($methods as $method) {
            $entry = $this->addRoute(strtoupper($method), $uri, $action);
        }
        return $entry;
    }

    /**
     * Define a route group with shared attributes.
     *
     * @param array{prefix?: string, middleware?: list<string>} $attributes
     */
    public function group(array $attributes, Closure $callback): void
    {
        $this->group->push($attributes, $callback);
    }

    // ----------------------------------------------------------------
    // Dispatch
    // ----------------------------------------------------------------

    /**
     * Dispatch the given request and return a Response.
     */
    public function dispatch(Request $request): Response
    {
        $method = $request->method();
        $path   = '/' . trim($request->path(), '/');

        // 1. Try exact method match
        if (isset($this->routes[$method])) {
            foreach ($this->routes[$method] as $route) {
                $params = [];
                if ($route->matches($path, $params)) {
                    $this->currentRoute = $route;
                    return $this->runRouteWithinStack($route, $params, $request);
                }
            }
        }

        // 2. Check if path exists on other methods → 405
        $allowedMethods = $this->getAllowedMethods($path);
        if (!empty($allowedMethods)) {
            return $this->createMethodNotAllowedResponse($path, $allowedMethods);
        }

        // 3. Not found
        return $this->createNotFoundResponse($path);
    }

    // ----------------------------------------------------------------
    // Named Routes & URL Generation
    // ----------------------------------------------------------------

    /**
     * Generate a URL for a named route.
     *
     * @param array<string, string> $params
     */
    public function url(string $name, array $params = []): string
    {
        if (!isset($this->namedRoutes[$name])) {
            throw new \RuntimeException("Route [{$name}] not defined.");
        }

        $uri = $this->namedRoutes[$name]->getUri();

        // Replace required params
        foreach ($params as $key => $value) {
            $uri = str_replace('{' . $key . '}', (string) $value, $uri);
            $uri = str_replace('{' . $key . '?}', (string) $value, $uri);
        }

        // Remove unfilled optional params
        $uri = preg_replace('/\/?\{[a-zA-Z_][a-zA-Z0-9_]*\?\}/', '', $uri);

        return $uri;
    }

    /**
     * Find a route by name.
     */
    public function findByName(string $name): ?RouteEntry
    {
        return $this->namedRoutes[$name] ?? null;
    }

    // ----------------------------------------------------------------
    // Inspection
    // ----------------------------------------------------------------

    /**
     * Get all registered routes.
     *
     * @return array<string, list<RouteEntry>>
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * Get all named routes.
     *
     * @return array<string, RouteEntry>
     */
    public function getNamedRoutes(): array
    {
        return $this->namedRoutes;
    }

    /**
     * Get the total number of registered routes.
     */
    public function count(): int
    {
        $count = 0;
        foreach ($this->routes as $entries) {
            $count += count($entries);
        }
        return $count;
    }

    /**
     * Get the currently matched route.
     */
    public function current(): ?RouteEntry
    {
        return $this->currentRoute;
    }

    // ----------------------------------------------------------------
    // Route Cache
    // ----------------------------------------------------------------

    /**
     * Compile all routes to a cacheable PHP file.
     */
    public function compileToCache(string $path): void
    {
        $data = [];

        foreach ($this->routes as $method => $entries) {
            foreach ($entries as $entry) {
                $action = $entry->getAction();

                // Closures cannot be cached — skip or throw
                if ($action instanceof Closure) {
                    continue;
                }

                $data[] = $entry->toArray();
            }
        }

        $content = '<?php return ' . var_export($data, true) . ';' . PHP_EOL;

        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($path, $content);
    }

    /**
     * Load routes from a cached PHP file.
     */
    public function loadFromCache(string $path): bool
    {
        if (!file_exists($path)) {
            return false;
        }

        $data = require $path;

        if (!is_array($data)) {
            return false;
        }

        foreach ($data as $item) {
            $entry = $this->addRoute($item['method'], $item['uri'], $item['action']);

            if ($item['name'] !== null) {
                $entry->name($item['name']);
                $this->namedRoutes[$item['name']] = $entry;
            }

            if (!empty($item['constraints'])) {
                $entry->whereAll($item['constraints']);
            }

            if (!empty($item['middleware'])) {
                $entry->middleware(...$item['middleware']);
            }
        }

        return true;
    }

    // ----------------------------------------------------------------
    // Internals
    // ----------------------------------------------------------------

    /**
     * Add a route to the internal registry.
     */
    protected function addRoute(string $method, string $uri, Closure|array $action): RouteEntry
    {
        // Apply group prefix
        $prefix = $this->group->getPrefix();
        $uri = $prefix . '/' . trim($uri, '/');
        $uri = '/' . trim($uri, '/');

        $entry = new RouteEntry($method, $uri, $action);

        // Apply group middleware
        $groupMiddleware = $this->group->getMiddleware();
        if (!empty($groupMiddleware)) {
            $entry->middleware(...$groupMiddleware);
        }

        $this->routes[$method][] = $entry;

        // Track URI → methods for 405 detection
        $this->uriMethodMap[$uri][] = $method;

        return $entry;
    }

    /**
     * Run the matched route through its middleware pipeline.
     */
    protected function runRouteWithinStack(RouteEntry $route, array $params, Request $request): Response
    {
        $middleware = $route->getMiddleware();

        return (new Pipeline(\Fly\Container\Container::getInstance()))
            ->send($request)
            ->through($middleware)
            ->then(fn (Request $req) => $this->callAction($route, $params, $req));
    }

    /**
     * Call the matched route action and normalize the return to a Response.
     *
     * @param array<string, string> $params
     */
    protected function callAction(RouteEntry $route, array $params, Request $request): Response
    {
        $action = $route->getAction();

        if ($action instanceof Closure) {
            $result = $action($request, ...$params);
        } else {
            [$controller, $method] = $action;

            if (is_string($controller)) {
                // Resolve via container if available
                $container = \Fly\Container\Container::getInstance();
                $controller = $container?->make($controller) ?? new $controller();
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
     * Get allowed HTTP methods for a given path (for 405 responses).
     *
     * @return list<string>
     */
    protected function getAllowedMethods(string $path): array
    {
        $allowed = [];

        foreach ($this->routes as $method => $entries) {
            foreach ($entries as $route) {
                if ($route->matches($path)) {
                    $allowed[] = $method;
                    break;
                }
            }
        }

        return $allowed;
    }

    /**
     * Create a 404 Not Found response.
     */
    protected function createNotFoundResponse(string $path): Response
    {
        throw new \Fly\Exceptions\NotFoundException("The requested path [{$path}] could not be found on this server.");
    }

    /**
     * Create a 405 Method Not Allowed response.
     *
     * @param list<string> $allowedMethods
     */
    protected function createMethodNotAllowedResponse(string $path, array $allowedMethods): Response
    {
        $allow = implode(', ', $allowedMethods);

        throw new \Fly\Exceptions\MethodNotAllowedException(
            "The [{$path}] path does not support the current HTTP method. Supported methods: [{$allow}].",
            null,
            ['Allow' => $allow]
        );
    }
}
