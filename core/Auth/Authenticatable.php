<?php

declare(strict_types=1);

namespace Fly\Auth;

trait Authenticatable
{
    /**
     * Get the name of the unique identifier for the user.
     */
    public function getAuthIdentifierName(): string
    {
        return $this->primaryKey ?? 'id';
    }

    /**
     * Get the unique identifier for the user.
     */
    public function getAuthIdentifier(): mixed
    {
        return $this->{$this->getAuthIdentifierName()};
    }

    /**
     * Get the password for the user.
     */
    public function getAuthPassword(): string
    {
        return $this->password;
    }

    /**
     * Get the token value for the "remember me" session.
     */
    public function getRememberToken(): ?string
    {
        if (!empty($this->getRememberTokenName())) {
            return (string) $this->{$this->getRememberTokenName()};
        }

        return null;
    }

    /**
     * Set the token value for the "remember me" session.
     */
    public function setRememberToken(string $value): void
    {
        if (!empty($this->getRememberTokenName())) {
            $this->{$this->getRememberTokenName()} = $value;
        }
    }

    /**
     * Get the column name for the "remember me" token.
     */
    public function getRememberTokenName(): string
    {
        return 'remember_token';
    }
}
