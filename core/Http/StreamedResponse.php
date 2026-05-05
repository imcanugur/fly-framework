<?php

declare(strict_types=1);

namespace Fly\Http;

/**
 * Streamed HTTP Response.
 *
 * Instead of buffering the entire body, the callback
 * is executed during send() to stream content directly.
 *
 * Usage:
 *   Response::stream(function () {
 *       echo 'chunk 1';
 *       flush();
 *       echo 'chunk 2';
 *   });
 */
class StreamedResponse extends Response
{
    protected $callback;

    /** @param array<string, string> $headers */
    public function __construct(callable $callback, int $statusCode = 200, array $headers = [])
    {
        parent::__construct('', $statusCode, $headers);
        $this->callback = $callback;
    }

    /**
     * Send the streamed response.
     */
    public function send(): void
    {
        $this->sendHeaders();
        $this->sendCookies();
        $this->sendContent();
    }

    /**
     * Execute the streaming callback.
     */
    protected function sendContent(): void
    {
        ($this->callback)();
    }
}
