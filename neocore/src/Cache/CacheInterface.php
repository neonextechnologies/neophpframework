<?php

declare(strict_types=1);

namespace NeoCore\Cache;

interface CacheInterface
{
    /**
     * Retrieve an item from the cache by key
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Store an item in the cache
     */
    public function put(string $key, mixed $value, int $ttl = 3600): bool;

    /**
     * Store an item in the cache indefinitely
     */
    public function forever(string $key, mixed $value): bool;

    /**
     * Retrieve an item from the cache, or store the default value if it doesn't exist
     */
    public function remember(string $key, int $ttl, callable $callback): mixed;

    /**
     * Retrieve an item from the cache, or store the default value indefinitely if it doesn't exist
     */
    public function rememberForever(string $key, callable $callback): mixed;

    /**
     * Increment the value of an item in the cache
     */
    public function increment(string $key, int $value = 1): int|false;

    /**
     * Decrement the value of an item in the cache
     */
    public function decrement(string $key, int $value = 1): int|false;

    /**
     * Remove an item from the cache
     */
    public function forget(string $key): bool;

    /**
     * Remove all items from the cache
     */
    public function flush(): bool;

    /**
     * Determine if an item exists in the cache
     */
    public function has(string $key): bool;

    /**
     * Retrieve multiple items from the cache by key
     */
    public function many(array $keys): array;

    /**
     * Store multiple items in the cache
     */
    public function putMany(array $values, int $ttl = 3600): bool;

    /**
     * Get the cache key prefix
     */
    public function getPrefix(): string;
}
