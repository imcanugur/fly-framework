<?php

declare(strict_types=1);

namespace Fly\Http;

/**
 * Native HTTP Request abstraction.
 *
 * Captures PHP superglobals and exposes a clean, readable API.
 * No external HTTP libraries — this is our own implementation.
 *
 * Phase 2: Full HTTP engine with JSON body parsing, file uploads,
 * cookie access, method spoofing, content negotiation, and input filtering.
 */
class Request
{
    /**
     * Parsed JSON body (lazy-loaded).
     */
    protected ?array $jsonBody = null;

    /**
     * Raw request body (lazy-loaded).
     */
    protected ?string $rawBody = null;

    /**
     * The session store instance.
     */
    protected ?\Fly\Session\Store $session = null;

    /**
     * @param array<string, string>   $server  Parsed $_SERVER
     * @param array<string, mixed>    $query   Parsed $_GET
     * @param array<string, mixed>    $post    Parsed $_POST
     * @param array<string, mixed>    $cookies Parsed $_COOKIE
     * @param array<string, mixed>    $files   Parsed $_FILES
     * @param array<string, string>   $headers Extracted HTTP headers
     */
    public function __construct(
        protected readonly array $server  = [],
        protected readonly array $query   = [],
        protected readonly array $post    = [],
        protected readonly array $cookies = [],
        protected readonly array $files   = [],
        protected readonly array $headers = [],
    ) {}

    /**
     * Create a Request from PHP superglobals.
     */
    public static function capture(): static
    {
        return new static(
            server:  $_SERVER,
            query:   $_GET,
            post:    $_POST,
            cookies: $_COOKIE,
            files:   $_FILES,
            headers: static::extractHeaders($_SERVER),
        );
    }

    /**
     * Create a synthetic Request for testing or internal dispatch.
     */
    public static function create(
        string $method = 'GET',
        string $uri = '/',
        array $query = [],
        array $post = [],
        array $headers = [],
        array $cookies = [],
        array $server = [],
    ): static {
        $server = array_merge([
            'REQUEST_METHOD' => strtoupper($method),
            'REQUEST_URI'    => $uri,
            'SERVER_NAME'    => 'localhost',
            'SERVER_PORT'    => '80',
            'HTTP_HOST'      => 'localhost',
        ], $server);

        // Merge headers into server format
        foreach ($headers as $key => $value) {
            $serverKey = 'HTTP_' . strtoupper(str_replace('-', '_', $key));
            $server[$serverKey] = $value;
        }

        $extractedHeaders = static::extractHeaders($server);

        return new static(
            server:  $server,
            query:   $query,
            post:    $post,
            cookies: $cookies,
            files:   [],
            headers: $extractedHeaders,
        );
    }

    // ----------------------------------------------------------------
    // HTTP Method
    // ----------------------------------------------------------------

    /**
     * Get the HTTP method (GET, POST, PUT, DELETE, etc.)
     *
     * Supports method spoofing via _method field or X-HTTP-Method-Override header.
     */
    public function method(): string
    {
        $method = strtoupper($this->server['REQUEST_METHOD'] ?? 'GET');

        // Method spoofing: allow forms to send PUT/PATCH/DELETE
        if ($method === 'POST') {
            $override = $this->input('_method')
                ?? $this->header('x-http-method-override');

            if ($override !== null) {
                return strtoupper((string) $override);
            }
        }

        return $method;
    }

    /**
     * Get the real HTTP method (ignoring spoofing).
     */
    public function realMethod(): string
    {
        return strtoupper($this->server['REQUEST_METHOD'] ?? 'GET');
    }

    /**
     * Check if the request uses the given method.
     */
    public function isMethod(string $method): bool
    {
        return $this->method() === strtoupper($method);
    }

    // ----------------------------------------------------------------
    // URI & Path
    // ----------------------------------------------------------------

    /**
     * Get the request URI (e.g., /users?page=1).
     */
    public function uri(): string
    {
        return $this->server['REQUEST_URI'] ?? '/';
    }

    /**
     * Get the request path without query string (e.g., /users).
     */
    public function path(): string
    {
        $uri = $this->uri();
        $pos = strpos($uri, '?');

        return $pos !== false ? substr($uri, 0, $pos) : $uri;
    }

    /**
     * Get the full URL of the request.
     */
    public function fullUrl(): string
    {
        $scheme = $this->isSecure() ? 'https' : 'http';
        $host   = $this->server['HTTP_HOST'] ?? 'localhost';

        return $scheme . '://' . $host . $this->uri();
    }

    /**
     * Get the URL without query string.
     */
    public function url(): string
    {
        $scheme = $this->isSecure() ? 'https' : 'http';
        $host   = $this->server['HTTP_HOST'] ?? 'localhost';

        return $scheme . '://' . $host . $this->path();
    }

    /**
     * Get the query string.
     */
    public function queryString(): string
    {
        return $this->server['QUERY_STRING'] ?? '';
    }

    /**
     * Get a segment of the URI path (1-indexed).
     *
     * Example: /users/42/posts → segment(1) = "users", segment(2) = "42"
     */
    public function segment(int $index, ?string $default = null): ?string
    {
        $segments = array_values(array_filter(explode('/', $this->path()), fn($s) => $s !== ''));

        return $segments[$index - 1] ?? $default;
    }

    // ----------------------------------------------------------------
    // Input (Query + Post + JSON Body)
    // ----------------------------------------------------------------

    /**
     * Get a single input value from POST, then query, then JSON body.
     */
    public function input(string $key, mixed $default = null): mixed
    {
        return $this->post[$key]
            ?? $this->query[$key]
            ?? $this->json($key)
            ?? $default;
    }

    /**
     * Get all input data (merged: query + post + json body).
     */
    public function all(): array
    {
        return array_merge($this->query, $this->post, $this->jsonBody() ?? []);
    }

    /**
     * Get only the specified keys from input.
     *
     * @param list<string> $keys
     */
    public function only(array $keys): array
    {
        $all = $this->all();
        return array_intersect_key($all, array_flip($keys));
    }

    /**
     * Get everything except the specified keys from input.
     *
     * @param list<string> $keys
     */
    public function except(array $keys): array
    {
        $all = $this->all();
        return array_diff_key($all, array_flip($keys));
    }

    /**
     * Determine if input contains a given key.
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->all());
    }

    /**
     * Determine if input contains a non-empty value for the key.
     */
    public function filled(string $key): bool
    {
        $value = $this->input($key);
        return $value !== null && $value !== '' && $value !== [];
    }

    /**
     * Get a query parameter.
     */
    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    /**
     * Get all query parameters.
     */
    public function queryAll(): array
    {
        return $this->query;
    }

    /**
     * Get a POST parameter.
     */
    public function post(string $key, mixed $default = null): mixed
    {
        return $this->post[$key] ?? $default;
    }

    // ----------------------------------------------------------------
    // JSON Body
    // ----------------------------------------------------------------

    /**
     * Get a value from the JSON request body.
     */
    public function json(string $key, mixed $default = null): mixed
    {
        $body = $this->jsonBody();

        return $body[$key] ?? $default;
    }

    /**
     * Get the entire parsed JSON body.
     */
    public function jsonBody(): ?array
    {
        if ($this->jsonBody !== null) {
            return $this->jsonBody;
        }

        if (!$this->isJson()) {
            return null;
        }

        $raw = $this->rawBody();

        if ($raw === '' || $raw === null) {
            return null;
        }

        $decoded = json_decode($raw, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        $this->jsonBody = $decoded;

        return $this->jsonBody;
    }

    /**
     * Get the raw request body.
     */
    public function rawBody(): string
    {
        if ($this->rawBody === null) {
            $this->rawBody = (string) file_get_contents('php://input');
        }

        return $this->rawBody;
    }

    // ----------------------------------------------------------------
    // Headers
    // ----------------------------------------------------------------

    /**
     * Get a specific header value.
     */
    public function header(string $key, ?string $default = null): ?string
    {
        $normalized = strtolower($key);
        return $this->headers[$normalized] ?? $default;
    }

    /**
     * Get all headers.
     *
     * @return array<string, string>
     */
    public function headers(): array
    {
        return $this->headers;
    }

    /**
     * Determine if a header exists.
     */
    public function hasHeader(string $key): bool
    {
        return isset($this->headers[strtolower($key)]);
    }

    /**
     * Get the bearer token from the Authorization header.
     */
    public function bearerToken(): ?string
    {
        $header = $this->header('authorization', '');

        if (str_starts_with($header, 'Bearer ')) {
            return substr($header, 7);
        }

        return null;
    }

    // ----------------------------------------------------------------
    // Cookies
    // ----------------------------------------------------------------

    /**
     * Get a cookie value.
     */
    public function cookie(string $key, ?string $default = null): ?string
    {
        return $this->cookies[$key] ?? $default;
    }

    /**
     * Get all cookies.
     */
    public function cookies(): array
    {
        return $this->cookies;
    }

    /**
     * Determine if a cookie exists.
     */
    public function hasCookie(string $key): bool
    {
        return isset($this->cookies[$key]);
    }

    // ----------------------------------------------------------------
    // Session
    // ----------------------------------------------------------------

    /**
     * Get the session associated with the request.
     */
    public function session(): \Fly\Session\Store
    {
        if (!$this->session) {
            throw new \RuntimeException('Session has not been started.');
        }

        return $this->session;
    }

    /**
     * Set the session associated with the request.
     */
    public function setSession(\Fly\Session\Store $session): void
    {
        $this->session = $session;
    }

    // ----------------------------------------------------------------
    // Files
    // ----------------------------------------------------------------

    /**
     * Get an uploaded file by key.
     *
     * @return UploadedFile|null
     */
    public function file(string $key): ?UploadedFile
    {
        if (!isset($this->files[$key])) {
            return null;
        }

        $file = $this->files[$key];

        return new UploadedFile(
            path:         $file['tmp_name'],
            originalName: $file['name'],
            mimeType:     $file['type'],
            size:         $file['size'],
            error:        $file['error'],
        );
    }

    /**
     * Determine if the request has an uploaded file.
     */
    public function hasFile(string $key): bool
    {
        return isset($this->files[$key]) && $this->files[$key]['error'] !== UPLOAD_ERR_NO_FILE;
    }

    // ----------------------------------------------------------------
    // Server
    // ----------------------------------------------------------------

    /**
     * Get a server variable.
     */
    public function server(string $key, ?string $default = null): ?string
    {
        return $this->server[$key] ?? $default;
    }

    /**
     * Get the client IP address.
     */
    public function ip(): string
    {
        return $this->server['HTTP_X_FORWARDED_FOR']
            ?? $this->server['HTTP_CLIENT_IP']
            ?? $this->server['REMOTE_ADDR']
            ?? '127.0.0.1';
    }

    /**
     * Get the user agent string.
     */
    public function userAgent(): ?string
    {
        return $this->header('user-agent');
    }

    // ----------------------------------------------------------------
    // Content Negotiation
    // ----------------------------------------------------------------

    /**
     * Determine if the request is a JSON request.
     */
    public function isJson(): bool
    {
        $contentType = $this->header('content-type', '');
        return str_contains($contentType, 'json');
    }

    /**
     * Determine if the request accepts JSON.
     */
    public function wantsJson(): bool
    {
        $accept = $this->header('accept', '');
        return str_contains($accept, 'json');
    }

    /**
     * Determine if the request is over HTTPS.
     */
    public function isSecure(): bool
    {
        return ($this->server['HTTPS'] ?? 'off') !== 'off';
    }

    /**
     * Determine if the request is an AJAX/XMLHttpRequest.
     */
    public function isAjax(): bool
    {
        return $this->header('x-requested-with') === 'XMLHttpRequest';
    }

    /**
     * Determine if the request expects a preflight (CORS OPTIONS).
     */
    public function isPreflight(): bool
    {
        return $this->method() === 'OPTIONS' && $this->hasHeader('access-control-request-method');
    }

    // ----------------------------------------------------------------
    // Internals
    // ----------------------------------------------------------------

    /**
     * Extract HTTP headers from the $_SERVER superglobal.
     *
     * @return array<string, string>
     */
    protected static function extractHeaders(array $server): array
    {
        $headers = [];

        foreach ($server as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = strtolower(str_replace('_', '-', substr($key, 5)));
                $headers[$name] = $value;
            }
        }

        if (isset($server['CONTENT_TYPE'])) {
            $headers['content-type'] = $server['CONTENT_TYPE'];
        }

        if (isset($server['CONTENT_LENGTH'])) {
            $headers['content-length'] = $server['CONTENT_LENGTH'];
        }

        return $headers;
    }
}
