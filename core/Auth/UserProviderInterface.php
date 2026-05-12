<?php

declare(strict_types=1);

namespace Fly\Auth;

interface UserProviderInterface
{
    /**
     * Retrieve a user by their unique identifier.
     */
    public function retrieveById(mixed $identifier): ?AuthenticatableInterface;

    /**
     * Retrieve a user by their "remember me" token.
     */
    public function retrieveByToken(mixed $identifier, string $token): ?AuthenticatableInterface;

    /**
     * Update the "remember me" token for the given user in storage.
     */
    public function updateRememberToken(AuthenticatableInterface $user, string $token): void;

    /**
     * Retrieve a user by the given credentials.
     */
    public function retrieveByCredentials(array $credentials): ?AuthenticatableInterface;

    /**
     * Validate a user against the given credentials.
     */
    public function validateCredentials(AuthenticatableInterface $user, array $credentials): bool;
}
