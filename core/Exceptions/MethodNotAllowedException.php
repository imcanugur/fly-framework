<?php

declare(strict_types=1);

namespace Fly\Exceptions;

class MethodNotAllowedException extends HttpException
{
    public function __construct(string $message = 'Method Not Allowed', ?\Throwable $previous = null, array $headers = [])
    {
        parent::__construct(405, $message, $previous, $headers);
    }
}
