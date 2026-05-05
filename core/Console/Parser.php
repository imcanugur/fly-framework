<?php

declare(strict_types=1);

namespace Fly\Console;

/**
 * Parses command signatures.
 * Example: `make:controller {name} {--force : Overwrite if exists}`
 */
class Parser
{
    /**
     * Parse the given console command signature into name, arguments, and options.
     *
     * @return array{0: string, 1: array, 2: array}
     */
    public static function parse(string $signature): array
    {
        preg_match('/^\s*([^\s]+)/', $signature, $matches);
        $name = $matches[1] ?? '';

        preg_match_all('/\{\s*(.*?)\s*\}/', $signature, $matches);

        $arguments = [];
        $options   = [];

        foreach ($matches[1] as $token) {
            $parts = explode(':', $token, 2);
            $token = trim($parts[0]);
            $description = trim($parts[1] ?? '');

            if (str_starts_with($token, '--')) {
                // Option
                $token = substr($token, 2);
                $hasValue = str_ends_with($token, '=');
                $token = rtrim($token, '=');

                $options[$token] = [
                    'name'        => $token,
                    'description' => $description,
                    'has_value'   => $hasValue,
                ];
            } else {
                // Argument
                $arguments[] = [
                    'name'        => $token,
                    'description' => $description,
                ];
            }
        }

        return [$name, $arguments, $options];
    }
}
