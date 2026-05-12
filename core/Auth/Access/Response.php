<?php

declare(strict_types=1);

namespace Fly\Auth\Access;

class Response
{
    /**
     * Create a new access response.
     */
    public function __construct(protected bool $allowed, protected ?string $message = null, protected ?string $code = null) {}

    /**
     * Create an allowed response.
     */
    public static function allow(?string $message = null, ?string $code = null): static
    {
        return new static(true, $message, $code);
    }

    /**
     * Create a denied response.
     */
    public static function deny(?string $message = null, ?string $code = null): static
    {
        return new static(false, $message, $code);
    }

    /**
     * Determine if the response was allowed.
     */
    public function allowed(): bool
    {
        return $this->allowed;
    }

    /**
     * Determine if the response was denied.
     */
    public function denied(): bool
    {
        return !$this->allowed();
    }

    /**
     * Get the response message.
     */
    public function message(): ?string
    {
        return $this->message;
    }

    /**
     * Get the response code.
     */
    public function code(): ?string
    {
        return $this->code;
    }

    /**
     * Convert the response to a boolean.
     */
    public function __toString(): string
    {
        return (string) $this->allowed();
    }
}
