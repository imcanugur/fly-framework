<?php

declare(strict_types=1);

namespace Fly\Exceptions;

class NotFoundException extends HttpException
{
    public function __construct(string $message = 'Not Found', ?\Throwable $previous = null, array $headers = [])
    {
        parent::__construct(404, $message, $previous, $headers);
    }
}
