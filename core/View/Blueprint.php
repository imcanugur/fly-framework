<?php

declare(strict_types=1);

namespace Fly\View;

class Blueprint
{
    public static function validate(array $data, array $types): void
    {
        foreach ($types as $key => $expectedType) {
            if (!isset($data[$key])) {
                throw new \RuntimeException("View Contract Violation: Missing required variable [\${$key}].");
            }

            $value = $data[$key];
            $actualType = gettype($value);

            if ($actualType === 'object') {
                if (!($value instanceof $expectedType)) {
                    $class = get_class($value);
                    throw new \RuntimeException("View Contract Violation: Variable [\${$key}] must be instance of [{$expectedType}], [{$class}] given.");
                }
            } else {
                if ($expectedType === 'string' && !is_string($value)) {
                    throw new \RuntimeException("View Contract Violation: Variable [\${$key}] must be [string], [{$actualType}] given.");
                }
                if ($expectedType === 'int' && !is_int($value)) {
                    throw new \RuntimeException("View Contract Violation: Variable [\${$key}] must be [int], [{$actualType}] given.");
                }
                if ($expectedType === 'array' && !is_array($value)) {
                    throw new \RuntimeException("View Contract Violation: Variable [\${$key}] must be [array], [{$actualType}] given.");
                }
                if ($expectedType === 'bool' && !is_bool($value)) {
                    throw new \RuntimeException("View Contract Violation: Variable [\${$key}] must be [bool], [{$actualType}] given.");
                }
            }
        }
    }
}
