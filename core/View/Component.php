<?php

declare(strict_types=1);

namespace Fly\View;

abstract class Component
{
    protected array $attributes = [];

    public function __construct(array $attributes = [])
    {
        $this->attributes = $attributes;
    }

    abstract public function render(): View|string;

    public function data(): array
    {
        $data = get_object_vars($this);
        unset($data['attributes']);
        return $data;
    }
}
