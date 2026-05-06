<?php

declare(strict_types=1);

namespace Fly\View;

class ComponentAttributeBag implements \ArrayAccess, \IteratorAggregate
{
    protected array $attributes = [];

    public function __construct(array $attributes = [])
    {
        $this->attributes = $attributes;
    }

    public function merge(array $defaults = []): self
    {
        $attributes = $this->attributes;
        
        foreach ($defaults as $key => $value) {
            if ($key === 'class') {
                $attributes['class'] = trim(($attributes['class'] ?? '') . ' ' . $value);
            } elseif (!array_key_exists($key, $attributes)) {
                $attributes[$key] = $value;
            }
        }
        
        return new static($attributes);
    }
    
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }
    
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->attributes);
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->attributes);
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->attributes[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->attributes[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (is_null($offset)) {
            $this->attributes[] = $value;
        } else {
            $this->attributes[$offset] = $value;
        }
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->attributes[$offset]);
    }

    public function __toString(): string
    {
        $html = '';
        foreach ($this->attributes as $key => $value) {
            if (is_bool($value) && $value) {
                $html .= " {$key}";
            } elseif (!is_bool($value)) {
                $html .= " {$key}=\"".htmlspecialchars((string)$value, ENT_QUOTES)."\"";
            }
        }
        return trim($html);
    }

    /**
     * Compile conditional classes for @class
     */
    public static function compileClass(array|string $classes): string
    {
        if (is_string($classes)) {
            return htmlspecialchars($classes, ENT_QUOTES);
        }

        $result = [];
        foreach ($classes as $class => $condition) {
            if (is_numeric($class)) {
                $result[] = $condition;
            } elseif ($condition) {
                $result[] = $class;
            }
        }

        return htmlspecialchars(implode(' ', $result), ENT_QUOTES);
    }
}
