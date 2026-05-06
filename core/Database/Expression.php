<?php

declare(strict_types=1);

namespace Fly\Database;

/**
 * Raw SQL Expression.
 *
 * Allows injecting raw SQL into Query Builder without parameterization.
 */
class Expression
{
    public function __construct(protected readonly string $value) {}

    public function getValue(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
