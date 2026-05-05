<?php

declare(strict_types=1);

namespace Fly\Http;

/**
 * Native HTTP Response abstraction.
 *
 * Phase 2: Full HTTP engine with HTML, file downloads,
 * streamed responses, cookies, and comprehensive status codes.
 */
class Response
{
    protected const array STATUS_TEXTS = [
        100 => 'Continue', 101 => 'Switching Protocols',
        200 => 'OK', 201 => 'Created', 202 => 'Accepted', 204 => 'No Content',
        301 => 'Moved Permanently', 302 => 'Found', 303 => 'See Other',
        304 => 'Not Modified', 307 => 'Temporary Redirect', 308 => 'Permanent Redirect',
        400 => 'Bad Request', 401 => 'Unauthorized', 403 => 'Forbidden',
        404 => 'Not Found', 405 => 'Method Not Allowed', 409 => 'Conflict',
        422 => 'Unprocessable Content', 429 => 'Too Many Requests',
        500 => 'Internal Server Error', 502 => 'Bad Gateway', 503 => 'Service Unavailable',
    ];

    /** @var list<array{name: string, value: string, options: array}> */
    protected array $cookies = [];

    /** @param array<string, string> $headers */
    public function __construct(
        protected string $content = '',
        protected int    $statusCode = 200,
        protected array  $headers = [],
    ) {}

    // --- Factory Methods ---

    public static function make(string $content = '', int $status = 200, array $headers = []): static
    {
        return new static($content, $status, $headers);
    }

    public static function html(string $html, int $status = 200, array $headers = []): static
    {
        $headers['Content-Type'] = 'text/html; charset=UTF-8';
        return new static($html, $status, $headers);
    }

    public static function json(mixed $data, int $status = 200, array $headers = []): static
    {
        $headers['Content-Type'] = 'application/json';
        return new static(
            json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            $status, $headers,
        );
    }

    public static function redirect(string $url, int $status = 302): static
    {
        return new static('', $status, ['Location' => $url]);
    }

    public static function download(string $filePath, ?string $filename = null): static
    {
        if (!file_exists($filePath)) {
            return new static('File not found.', 404);
        }
        $filename = $filename ?? basename($filePath);
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($filePath) ?: 'application/octet-stream';
        return new static(
            (string) file_get_contents($filePath), 200, [
                'Content-Type'        => $mime,
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Content-Length'      => (string) filesize($filePath),
            ],
        );
    }

    public static function stream(callable $callback, int $status = 200, array $headers = []): StreamedResponse
    {
        return new StreamedResponse($callback, $status, $headers);
    }

    public static function noContent(int $status = 204): static
    {
        return new static('', $status);
    }

    // --- Mutators ---

    public function setContent(string $content): static { $this->content = $content; return $this; }
    public function setStatusCode(int $code): static { $this->statusCode = $code; return $this; }
    public function setHeader(string $name, string $value): static { $this->headers[$name] = $value; return $this; }
    public function removeHeader(string $name): static { unset($this->headers[$name]); return $this; }

    public function withCookie(
        string $name, string $value = '', int $minutes = 0,
        string $path = '/', string $domain = '', bool $secure = false,
        bool $httpOnly = true, string $sameSite = 'Lax',
    ): static {
        $this->cookies[] = [
            'name' => $name, 'value' => $value,
            'options' => [
                'expires' => $minutes > 0 ? time() + ($minutes * 60) : 0,
                'path' => $path, 'domain' => $domain, 'secure' => $secure,
                'httponly' => $httpOnly, 'samesite' => $sameSite,
            ],
        ];
        return $this;
    }

    public function withoutCookie(string $name, string $path = '/'): static
    {
        return $this->withCookie($name, '', minutes: -525600, path: $path);
    }

    // --- Accessors ---

    public function getContent(): string { return $this->content; }
    public function getStatusCode(): int { return $this->statusCode; }
    public function getStatusText(): string { return static::STATUS_TEXTS[$this->statusCode] ?? 'Unknown'; }
    /** @return array<string, string> */
    public function getHeaders(): array { return $this->headers; }

    public function isSuccessful(): bool { return $this->statusCode >= 200 && $this->statusCode < 300; }
    public function isRedirect(): bool { return $this->statusCode >= 300 && $this->statusCode < 400; }
    public function isClientError(): bool { return $this->statusCode >= 400 && $this->statusCode < 500; }
    public function isServerError(): bool { return $this->statusCode >= 500; }

    // --- Emitter ---

    public function send(): void
    {
        $this->sendHeaders();
        $this->sendCookies();
        $this->sendContent();
    }

    protected function sendHeaders(): void
    {
        if (headers_sent()) { return; }
        http_response_code($this->statusCode);
        foreach ($this->headers as $name => $value) {
            header("{$name}: {$value}", true);
        }
    }

    protected function sendCookies(): void
    {
        if (headers_sent()) { return; }
        foreach ($this->cookies as $cookie) {
            setcookie($cookie['name'], $cookie['value'], $cookie['options']);
        }
    }

    protected function sendContent(): void
    {
        echo $this->content;
    }
}
