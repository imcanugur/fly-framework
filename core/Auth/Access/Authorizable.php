<?php

declare(strict_types=1);

namespace Fly\Auth\Access;

trait Authorizable
{
    /**
     * Determine if the entity has a given ability.
     */
    public function can(string $ability, mixed $arguments = []): bool
    {
        return app(Gate::class)->forUser($this)->check($ability, $arguments);
    }

    /**
     * Determine if the entity does not have a given ability.
     */
    public function cannot(string $ability, mixed $arguments = []): bool
    {
        return !$this->can($ability, $arguments);
    }
}
