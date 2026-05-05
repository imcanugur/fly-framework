<?php

declare(strict_types=1);

namespace Fly\Http;

/**
 * Native HTTP Response abstraction.
 *
 * Manages status code, headers, and body content.
 * Emits the response using native PHP functions.
 */
class Response
{
    /**
     * HTTP status text map.
     */
    protected const array STATUS_TEXTS = [
        200 => 'OK',
        201 => 'Created',
        204 => 'No Content',
        301 => 'Moved Permanently',
        302 => 'Found',
        304 => 'Not Modified',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        500 => 'Internal Server Error',
    ];

    /**
     * @param array<string, string> $headers
     */
    public function __construct(
        protected string $content = '',
        protected int    $statusCode = 200,
        protected array  $headers = [],
    ) {}

    // ----------------------------------------------------------------
    // Factory Methods
    // ----------------------------------------------------------------

    /**
     * Create a plain text response.
     */
    public static function make(string $content = '', int $status = 200, array $headers = []): static
    {
        return new static($content, $status, $headers);
    }

    /**
     * Create a JSON response.
     */
    public static function json(mixed $data, int $status = 200, array $headers = []): static
    {
        $headers['Content-Type'] = 'application/json';

        return new static(
            json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            $status,
            $headers,
        );
    }

    /**
     * Create a redirect response.
     */
    public static function redirect(string $url, int $status = 302): static
    {
        return new static('', $status, ['Location' => $url]);
    }

    // ----------------------------------------------------------------
    // Mutators
    // ----------------------------------------------------------------

    /**
     * Set the response content.
     */
    public function setContent(string $content): static
    {
        $this->content = $content;
        return $this;
    }

    /**
     * Set the HTTP status code.
     */
    public function setStatusCode(int $code): static
    {
        $this->statusCode = $code;
        return $this;
    }

    /**
     * Set a response header.
     */
    public function setHeader(string $name, string $value): static
    {
        $this->headers[$name] = $value;
        return $this;
    }

    // ----------------------------------------------------------------
    // Accessors
    // ----------------------------------------------------------------

    public function getContent(): string
    {
        return $this->content;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @return array<string, string>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    // ----------------------------------------------------------------
    // Emitter
    // ----------------------------------------------------------------

    /**
     * Send the response to the browser.
     *
     * Uses native header(), http_response_code(), and echo.
     */
    public function send(): void
    {
        $this->sendHeaders();
        $this->sendContent();
    }

    /**
     * Send HTTP headers.
     */
    protected function sendHeaders(): void
    {
        if (headers_sent()) {
            return;
        }

        http_response_code($this->statusCode);

        foreach ($this->headers as $name => $value) {
            header("{$name}: {$value}", true);
        }
    }

    /**
     * Send the response body.
     */
    protected function sendContent(): void
    {
        echo $this->content;
    }
}
