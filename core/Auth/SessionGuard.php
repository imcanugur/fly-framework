<?php

declare(strict_types=1);

namespace Fly\Auth;

use Fly\Session\Store as SessionStore;

class SessionGuard implements GuardInterface
{
    /**
     * The name of the guard.
     */
    protected string $name;

    /**
     * The user provider implementation.
     */
    protected UserProviderInterface $provider;

    /**
     * The session store implementation.
     */
    protected SessionStore $session;

    /**
     * The currently authenticated user.
     */
    protected ?AuthenticatableInterface $user = null;

    /**
     * Create a new authentication guard.
     */
    public function __construct(string $name, UserProviderInterface $provider, SessionStore $session)
    {
        $this->name = $name;
        $this->provider = $provider;
        $this->session = $session;
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

        $id = $this->session->get($this->getName());

        if (!is_null($id)) {
            $this->user = $this->provider->retrieveById($id);
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

        return $this->session->get($this->getName());
    }

    /**
     * {@inheritdoc}
     */
    public function validate(array $credentials = []): bool
    {
        return !is_null($this->provider->retrieveByCredentials($credentials));
    }

    /**
     * Attempt to authenticate a user using the given credentials.
     */
    public function attempt(array $credentials = [], bool $remember = false): bool
    {
        $user = $this->provider->retrieveByCredentials($credentials);

        if ($user && $this->provider->validateCredentials($user, $credentials)) {
            $this->login($user, $remember);
            return true;
        }

        return false;
    }

    /**
     * Log a user into the application.
     */
    public function login(AuthenticatableInterface $user, bool $remember = false): void
    {
        $this->updateSession($user->getAuthIdentifier());

        if ($remember) {
            $this->ensureRememberTokenIsSet($user);
            $this->queueRecallableCookie($user);
        }

        $this->setUser($user);
    }

    /**
     * Log the user out of the application.
     */
    public function logout(): void
    {
        $this->session->forget($this->getName());
        $this->user = null;
    }

    /**
     * Update the session with the given ID.
     */
    protected function updateSession(mixed $id): void
    {
        $this->session->put($this->getName(), $id);
        $this->session->regenerate();
    }

    /**
     * Get a unique identifier for the auth session value.
     */
    public function getName(): string
    {
        return 'login_' . $this->name . '_' . sha1(static::class);
    }

    /**
     * {@inheritdoc}
     */
    public function setUser(AuthenticatableInterface $user): void
    {
        $this->user = $user;
    }

    /**
     * Ensure a remember token is set for the user.
     */
    protected function ensureRememberTokenIsSet(AuthenticatableInterface $user): void
    {
        if (empty($user->getRememberToken())) {
            $this->provider->updateRememberToken($user, \Fly\Support\Str::random(60));
        }
    }

    /**
     * Queue the "remember me" cookie.
     */
    protected function queueRecallableCookie(AuthenticatableInterface $user): void
    {
        // This would interact with the CookieJar
    }
}
