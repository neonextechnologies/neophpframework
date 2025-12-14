<?php

declare(strict_types=1);

namespace NeoCore\Auth\Hash;

/**
 * Bcrypt Hasher
 * 
 * Provides password hashing using bcrypt algorithm
 */
class BcryptHasher implements HasherInterface
{
    /**
     * Default bcrypt rounds
     */
    protected int $rounds = 10;

    /**
     * Create a new hasher instance
     */
    public function __construct(array $options = [])
    {
        $this->rounds = $options['rounds'] ?? $this->rounds;
    }

    /**
     * Hash the given value
     */
    public function make(string $value, array $options = []): string
    {
        $cost = $options['rounds'] ?? $this->rounds;

        if ($cost < 4 || $cost > 31) {
            throw new \InvalidArgumentException('Bcrypt rounds must be between 4 and 31');
        }

        $hash = password_hash($value, PASSWORD_BCRYPT, ['cost' => $cost]);

        if ($hash === false) {
            throw new \RuntimeException('Bcrypt hashing failed');
        }

        return $hash;
    }

    /**
     * Check the given plain value against a hash
     */
    public function check(string $value, string $hashedValue): bool
    {
        if (strlen($hashedValue) === 0) {
            return false;
        }

        return password_verify($value, $hashedValue);
    }

    /**
     * Check if the given hash needs to be rehashed
     */
    public function needsRehash(string $hashedValue, array $options = []): bool
    {
        $cost = $options['rounds'] ?? $this->rounds;

        return password_needs_rehash($hashedValue, PASSWORD_BCRYPT, ['cost' => $cost]);
    }

    /**
     * Get information about the given hashed value
     */
    public function info(string $hashedValue): array
    {
        return password_get_info($hashedValue);
    }

    /**
     * Set the default rounds for bcrypt
     */
    public function setRounds(int $rounds): self
    {
        $this->rounds = $rounds;
        return $this;
    }

    /**
     * Get the current rounds
     */
    public function getRounds(): int
    {
        return $this->rounds;
    }
}
