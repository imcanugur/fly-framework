<?php

declare(strict_types=1);

namespace Fly\Auth;

interface GuardInterface
{
    /**
     * Determine if the current user is authenticated.
     */
    public function check(): bool;

    /**
     * Determine if the current user is a guest.
     */
    public function guest(): bool;

    /**
     * Get the currently authenticated user.
     */
    public function user(): ?AuthenticatableInterface;

    /**
     * Get the ID for the currently authenticated user.
     */
    public function id(): mixed;

    /**
     * Validate a user's credentials.
     */
    public function validate(array $credentials = []): bool;

    /**
     * Set the current user.
     */
    public function setUser(AuthenticatableInterface $user): void;
}
