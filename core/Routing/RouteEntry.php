<?php

declare(strict_types=1);

namespace Fly\Routing;

use Closure;

/**
 * Represents a single registered route.
 *
 * Stores the HTTP method, URI pattern, action, name, parameter constraints,
 * and middleware list. Compiles the URI into a regex for fast matching.
 */
class RouteEntry
{
    protected string $compiledPattern;

    /** @var array<string, string> Parameter constraints (e.g., ['id' => '\d+']) */
    protected array $constraints = [];

    /** @var list<string> Middleware classes assigned to this route */
    protected array $middleware = [];

    protected ?string $name = null;

    public function __construct(
        protected readonly string       $method,
        protected readonly string       $uri,
        protected readonly Closure|array $action,
    ) {
        $this->compiledPattern = $this->compile();
    }

    // ----------------------------------------------------------------
    // Fluent Configuration
    // ----------------------------------------------------------------

    /**
     * Assign a name to this route (for URL generation).
     */
    public function name(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Add a parameter constraint.
     *
     * Example: Route::get('/users/{id}', ...)->where('id', '\d+');
     */
    public function where(string $param, string $regex): static
    {
        $this->constraints[$param] = $regex;
        $this->compiledPattern = $this->compile();
        return $this;
    }

    /**
     * Add multiple parameter constraints at once.
     *
     * @param array<string, string> $constraints
     */
    public function whereAll(array $constraints): static
    {
        foreach ($constraints as $param => $regex) {
            $this->constraints[$param] = $regex;
        }
        $this->compiledPattern = $this->compile();
        return $this;
    }

    /**
     * Shortcut: constrain parameter to numeric values only.
     */
    public function whereNumber(string $param): static
    {
        return $this->where($param, '\d+');
    }

    /**
     * Shortcut: constrain parameter to alphabetic values only.
     */
    public function whereAlpha(string $param): static
    {
        return $this->where($param, '[a-zA-Z]+');
    }

    /**
     * Shortcut: constrain parameter to alphanumeric values.
     */
    public function whereAlphaNumeric(string $param): static
    {
        return $this->where($param, '[a-zA-Z0-9]+');
    }

    /**
     * Assign middleware to this route.
     */
    public function middleware(string ...$middleware): static
    {
        $this->middleware = array_merge($this->middleware, $middleware);
        return $this;
    }

    // ----------------------------------------------------------------
    // Matching
    // ----------------------------------------------------------------

    /**
     * Test if the given path matches this route.
     *
     * @param array<string, string> $params Extracted named parameters (out-param)
     */
    public function matches(string $path, array &$params = []): bool
    {
        if (!preg_match($this->compiledPattern, $path, $matches)) {
            return false;
        }

        $params = array_filter(
            $matches,
            fn(string $key) => !is_numeric($key),
            ARRAY_FILTER_USE_KEY,
        );

        return true;
    }

    // ----------------------------------------------------------------
    // Accessors
    // ----------------------------------------------------------------

    public function getMethod(): string { return $this->method; }
    public function getUri(): string { return $this->uri; }
    public function getAction(): Closure|array { return $this->action; }
    public function getName(): ?string { return $this->name; }
    public function getPattern(): string { return $this->compiledPattern; }
    /** @return list<string> */
    public function getMiddleware(): array { return $this->middleware; }
    /** @return array<string, string> */
    public function getConstraints(): array { return $this->constraints; }

    /**
     * Determine if this route has dynamic parameters.
     */
    public function isDynamic(): bool
    {
        return str_contains($this->uri, '{');
    }

    /**
     * Export to a cacheable array (no closures allowed for cached routes).
     *
     * @return array{method: string, uri: string, action: array, name: ?string, constraints: array, middleware: list<string>}
     */
    public function toArray(): array
    {
        return [
            'method'      => $this->method,
            'uri'         => $this->uri,
            'action'      => $this->action, // must be [Controller::class, 'method'] for caching
            'name'        => $this->name,
            'constraints' => $this->constraints,
            'middleware'   => $this->middleware,
        ];
    }

    // ----------------------------------------------------------------
    // Compiler
    // ----------------------------------------------------------------

    /**
     * Compile the URI pattern into a regex.
     *
     * Handles:
     *   /users/{id}    → required param
     *   /users/{id?}   → optional param
     *   constraints    → custom regex per param
     */
    protected function compile(): string
    {
        $uri = $this->uri;

        // Replace optional parameters: {param?}
        $uri = preg_replace_callback(
            '/\/?\{([a-zA-Z_][a-zA-Z0-9_]*)\?\}/',
            function (array $m) {
                $param   = $m[1];
                $pattern = $this->constraints[$param] ?? '[^/]+';
                return '(?:/(?P<' . $param . '>' . $pattern . '))?';
            },
            $uri,
        );

        // Replace required parameters: {param}
        $uri = preg_replace_callback(
            '/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/',
            function (array $m) {
                $param   = $m[1];
                $pattern = $this->constraints[$param] ?? '[^/]+';
                return '(?P<' . $param . '>' . $pattern . ')';
            },
            $uri,
        );

        return '#^' . $uri . '$#';
    }
}
