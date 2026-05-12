<?php

declare(strict_types=1);

namespace Fly\Hashing;

use RuntimeException;

class BcryptHasher implements HasherInterface
{
    /**
     * The default cost factor.
     */
    protected int $rounds = 10;

    /**
     * {@inheritdoc}
     */
    public function make(string $value, array $options = []): string
    {
        $hash = password_hash($value, PASSWORD_BCRYPT, [
            'cost' => $options['rounds'] ?? $this->rounds,
        ]);

        if ($hash === false) {
            throw new RuntimeException('Bcrypt hashing failed.');
        }

        return $hash;
    }

    /**
     * {@inheritdoc}
     */
    public function check(string $value, string $hashedValue, array $options = []): bool
    {
        if ($hashedValue === '') {
            return false;
        }

        return password_verify($value, $hashedValue);
    }

    /**
     * {@inheritdoc}
     */
    public function needsRehash(string $hashedValue, array $options = []): bool
    {
        return password_needs_rehash($hashedValue, PASSWORD_BCRYPT, [
            'cost' => $options['rounds'] ?? $this->rounds,
        ]);
    }
}
