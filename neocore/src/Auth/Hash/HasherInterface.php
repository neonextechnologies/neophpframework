<?php

declare(strict_types=1);

namespace NeoCore\Auth\Hash;

/**
 * Hasher Interface
 * 
 * Defines methods for password hashing
 */
interface HasherInterface
{
    /**
     * Hash the given value
     */
    public function make(string $value, array $options = []): string;

    /**
     * Check the given plain value against a hash
     */
    public function check(string $value, string $hashedValue): bool;

    /**
     * Check if the given hash needs to be rehashed
     */
    public function needsRehash(string $hashedValue, array $options = []): bool;

    /**
     * Get information about the given hashed value
     */
    public function info(string $hashedValue): array;
}
