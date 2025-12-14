<?php

declare(strict_types=1);

use NeoCore\Auth\Hash\HashManager;

if (!function_exists('hash_make')) {
    /**
     * Hash the given value using bcrypt
     */
    function hash_make(string $value, array $options = []): string
    {
        return app(HashManager::class)->make($value, $options);
    }
}

if (!function_exists('hash_check')) {
    /**
     * Check the given plain value against a hash
     */
    function hash_check(string $value, string $hashedValue): bool
    {
        return app(HashManager::class)->check($value, $hashedValue);
    }
}

if (!function_exists('hash_needs_rehash')) {
    /**
     * Check if the given hash needs to be rehashed
     */
    function hash_needs_rehash(string $hashedValue, array $options = []): bool
    {
        return app(HashManager::class)->needsRehash($hashedValue, $options);
    }
}

if (!function_exists('hash_info')) {
    /**
     * Get information about the given hashed value
     */
    function hash_info(string $hashedValue): array
    {
        return app(HashManager::class)->info($hashedValue);
    }
}
