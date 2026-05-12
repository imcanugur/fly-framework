<?php

declare(strict_types=1);

namespace Fly\Auth;

use Fly\Http\Request;

class TokenGuard implements GuardInterface
{
    /**
     * The user provider implementation.
     */
    protected UserProviderInterface $provider;

    /**
     * The request instance.
     */
    protected Request $request;

    /**
     * The name of the query string item from the request containing the API token.
     */
    protected string $inputKey;

    /**
     * The name of the token "column" in persistent storage.
     */
    protected string $storageKey;

    /**
     * The currently authenticated user.
     */
    protected ?AuthenticatableInterface $user = null;

    /**
     * Create a new authentication guard.
     */
    public function __construct(
        UserProviderInterface $provider,
        Request $request,
        string $inputKey = 'api_token',
        string $storageKey = 'api_token'
    ) {
        $this->provider = $provider;
        $this->request = $request;
        $this->inputKey = $inputKey;
        $this->storageKey = $storageKey;
    }

    /**
     * {@inheritdoc}
     */
    public function check(): bool
    {
        return !is_null($this->user());
    }

    /**
     * {@inheritdoc}
     */
    public function guest(): bool
    {
        return !$this->check();
    }

    /**
     * {@inheritdoc}
     */
    public function user(): ?AuthenticatableInterface
    {
        if (!is_null($this->user)) {
            return $this->user;
        }

        $token = $this->getTokenForRequest();

        if (!empty($token)) {
            $this->user = $this->provider->retrieveByCredentials([
                $this->storageKey => $token,
            ]);
        }

        return $this->user;
    }

    /**
     * {@inheritdoc}
     */
    public function id(): mixed
    {
        if ($this->user()) {
            return $this->user()->getAuthIdentifier();
        }

        return null;
    }

    /**
     * Validate a user's credentials.
     */
    public function validate(array $credentials = []): bool
    {
        if (empty($credentials[$this->inputKey])) {
            return false;
        }

        $user = $this->provider->retrieveByCredentials([
            $this->storageKey => $credentials[$this->inputKey],
        ]);

        return !is_null($user);
    }

    /**
     * Set the current user.
     */
    public function setUser(AuthenticatableInterface $user): void
    {
        $this->user = $user;
    }

    /**
     * Get the token for the current request.
     */
    public function getTokenForRequest(): ?string
    {
        $token = $this->request->input($this->inputKey);

        if (empty($token)) {
            $token = $this->request->bearerToken();
        }

        return $token;
    }
}
