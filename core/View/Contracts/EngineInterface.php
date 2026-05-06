<?php

declare(strict_types=1);

namespace Fly\View\Contracts;

interface EngineInterface
{
    public function render(string $path, array $data = []): string;
}
