<?php

declare(strict_types=1);

namespace NeoCore\Auth\Hash;

/**
 * Argon2 Hasher
 * 
 * Provides password hashing using Argon2 algorithm
 */
class Argon2Hasher implements HasherInterface
{
    /**
     * Default memory cost
     */
    protected int $memory = 65536;

    /**
     * Default time cost
     */
    protected int $time = 4;

    /**
     * Default threads
     */
    protected int $threads = 1;

    /**
     * Create a new hasher instance
     */
    public function __construct(array $options = [])
    {
        $this->memory = $options['memory'] ?? $this->memory;
        $this->time = $options['time'] ?? $this->time;
        $this->threads = $options['threads'] ?? $this->threads;
    }

    /**
     * Hash the given value
     */
    public function make(string $value, array $options = []): string
    {
        $algorithm = $this->algorithm();

        if (!defined($algorithm)) {
            throw new \RuntimeException('Argon2 is not supported on this system');
        }

        $hash = password_hash($value, constant($algorithm), [
            'memory_cost' => $options['memory'] ?? $this->memory,
            'time_cost' => $options['time'] ?? $this->time,
            'threads' => $options['threads'] ?? $this->threads,
        ]);

        if ($hash === false) {
            throw new \RuntimeException('Argon2 hashing failed');
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
        $algorithm = $this->algorithm();

        return password_needs_rehash($hashedValue, constant($algorithm), [
            'memory_cost' => $options['memory'] ?? $this->memory,
            'time_cost' => $options['time'] ?? $this->time,
            'threads' => $options['threads'] ?? $this->threads,
        ]);
    }

    /**
     * Get information about the given hashed value
     */
    public function info(string $hashedValue): array
    {
        return password_get_info($hashedValue);
    }

    /**
     * Get the algorithm identifier
     */
    protected function algorithm(): string
    {
        if (defined('PASSWORD_ARGON2ID')) {
            return 'PASSWORD_ARGON2ID';
        }

        return 'PASSWORD_ARGON2I';
    }
}
