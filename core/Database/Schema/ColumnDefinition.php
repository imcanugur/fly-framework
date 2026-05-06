<?php

declare(strict_types=1);

namespace Fly\Database\Schema;

class ColumnDefinition
{
    public bool $nullable = false;
    public mixed $default = null;
    public bool $hasDefault = false;
    public bool $autoIncrement = false;
    public bool $unsigned = false;
    public bool $unique = false;

    public function __construct(
        public string $name,
        public string $type
    ) {}

    public function nullable(bool $value = true): self
    {
        $this->nullable = $value;
        return $this;
    }

    public function default(mixed $value): self
    {
        $this->default = $value;
        $this->hasDefault = true;
        return $this;
    }

    public function unsigned(): self
    {
        $this->unsigned = true;
        return $this;
    }

    public function unique(): self
    {
        $this->unique = true;
        return $this;
    }

    public function autoIncrement(): self
    {
        $this->autoIncrement = true;
        return $this;
    }
}
