<?php

declare(strict_types=1);

namespace Fly\Auth;

use Fly\Hashing\HasherInterface;
use Fly\Support\Str;

class EloquentUserProvider implements UserProviderInterface
{
    /**
     * The hasher implementation.
     */
    protected HasherInterface $hasher;

    /**
     * The Eloquent user model.
     */
    protected string $model;

    /**
     * Create a new database user provider.
     */
    public function __construct(HasherInterface $hasher, string $model)
    {
        $this->hasher = $hasher;
        $this->model = $model;
    }

    /**
     * {@inheritdoc}
     */
    public function retrieveById(mixed $identifier): ?AuthenticatableInterface
    {
        $model = $this->createModel();

        return $model->where($model->getAuthIdentifierName(), $identifier)->first();
    }

    /**
     * {@inheritdoc}
     */
    public function retrieveByToken(mixed $identifier, string $token): ?AuthenticatableInterface
    {
        $model = $this->createModel();

        $user = $model->where($model->getAuthIdentifierName(), $identifier)->first();

        if (!$user) {
            return null;
        }

        $rememberToken = $user->getRememberToken();

        return $rememberToken && hash_equals($rememberToken, $token) ? $user : null;
    }

    /**
     * {@inheritdoc}
     */
    public function updateRememberToken(AuthenticatableInterface $user, string $token): void
    {
        $user->setRememberToken($token);

        $user->save();
    }

    /**
     * {@inheritdoc}
     */
    public function retrieveByCredentials(array $credentials): ?AuthenticatableInterface
    {
        if (empty($credentials)) {
            return null;
        }

        $query = $this->createModel()->newQuery();

        foreach ($credentials as $key => $value) {
            if (Str::contains($key, 'password')) {
                continue;
            }

            $query->where($key, $value);
        }

        return $query->first();
    }

    /**
     * {@inheritdoc}
     */
    public function validateCredentials(AuthenticatableInterface $user, array $credentials): bool
    {
        $plain = $credentials['password'];

        return $this->hasher->check($plain, $user->getAuthPassword());
    }

    /**
     * Create a new instance of the model.
     */
    public function createModel(): AuthenticatableInterface
    {
        $class = '\\' . ltrim($this->model, '\\');

        return new $class;
    }
}
