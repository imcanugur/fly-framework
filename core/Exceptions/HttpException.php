<?php

declare(strict_types=1);

namespace Fly\Exceptions;

use RuntimeException;
use Throwable;

class HttpException extends RuntimeException
{
    public function __construct(
        protected int $statusCode,
        string $message = '',
        ?Throwable $previous = null,
        protected array $headers = []
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }
}
