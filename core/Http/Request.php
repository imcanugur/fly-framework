<?php

declare(strict_types=1);

namespace Fly\Http;

/**
 * Native HTTP Request abstraction.
 *
 * Captures PHP superglobals and exposes a clean, readable API.
 * No external HTTP libraries — this is our own implementation.
 */
class Request
{
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

    // ----------------------------------------------------------------
    // Core Accessors
    // ----------------------------------------------------------------

    /**
     * Get the HTTP method (GET, POST, PUT, DELETE, etc.)
     */
    public function method(): string
    {
        return strtoupper($this->server['REQUEST_METHOD'] ?? 'GET');
    }

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
     * Get a query parameter.
     */
    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    /**
     * Get a POST/body parameter.
     */
    public function input(string $key, mixed $default = null): mixed
    {
        return $this->post[$key] ?? $this->query[$key] ?? $default;
    }

    /**
     * Get all input (merged query + post).
     */
    public function all(): array
    {
        return array_merge($this->query, $this->post);
    }

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
     * Get the full URL of the request.
     */
    public function fullUrl(): string
    {
        $scheme = ($this->server['HTTPS'] ?? 'off') !== 'off' ? 'https' : 'http';
        $host   = $this->server['HTTP_HOST'] ?? 'localhost';

        return $scheme . '://' . $host . $this->uri();
    }

    /**
     * Determine if the request is JSON.
     */
    public function isJson(): bool
    {
        $contentType = $this->header('content-type', '');
        return str_contains($contentType, 'json');
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
