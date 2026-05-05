<?php

declare(strict_types=1);

namespace Fly\Routing;

use Closure;

/**
 * Manages route groups with shared attributes (prefix, middleware).
 *
 * Usage:
 *   Route::group(['prefix' => '/api', 'middleware' => [AuthMiddleware::class]], function () {
 *       Route::get('/users', ...);   // becomes /api/users with auth middleware
 *   });
 */
class RouteGroup
{
    /**
     * The active group attribute stack.
     *
     * @var list<array{prefix: string, middleware: list<string>}>
     */
    protected array $stack = [];

    /**
     * Push a new group onto the stack and execute the callback.
     *
     * @param array{prefix?: string, middleware?: list<string>} $attributes
     */
    public function push(array $attributes, Closure $callback): void
    {
        $this->stack[] = [
            'prefix'     => $this->resolvePrefix($attributes['prefix'] ?? ''),
            'middleware'  => $this->resolveMiddleware($attributes['middleware'] ?? []),
        ];

        $callback();

        array_pop($this->stack);
    }

    /**
     * Get the current group prefix.
     */
    public function getPrefix(): string
    {
        if (empty($this->stack)) {
            return '';
        }

        return end($this->stack)['prefix'];
    }

    /**
     * Get the current group middleware stack.
     *
     * @return list<string>
     */
    public function getMiddleware(): array
    {
        if (empty($this->stack)) {
            return [];
        }

        return end($this->stack)['middleware'];
    }

    /**
     * Determine if inside a group.
     */
    public function hasActive(): bool
    {
        return !empty($this->stack);
    }

    /**
     * Resolve the prefix by merging with parent group prefix.
     */
    protected function resolvePrefix(string $prefix): string
    {
        $parent = $this->getPrefix();
        $prefix = trim($prefix, '/');

        if ($parent === '' && $prefix === '') {
            return '';
        }

        if ($parent === '') {
            return '/' . $prefix;
        }

        if ($prefix === '') {
            return $parent;
        }

        return rtrim($parent, '/') . '/' . $prefix;
    }

    /**
     * Resolve middleware by merging with parent group middleware.
     *
     * @param list<string> $middleware
     * @return list<string>
     */
    protected function resolveMiddleware(array $middleware): array
    {
        return array_merge($this->getMiddleware(), $middleware);
    }
}
