<?php

declare(strict_types=1);

namespace Fly\View\Contracts;

interface CompilerInterface
{
    public function compile(string $value): string;
    public function directive(string $name, callable $handler): void;
}
