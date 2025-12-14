<?php

declare(strict_types=1);

use NeoCore\Cache\CacheManager;
use NeoCore\Cache\CacheInterface;

if (!function_exists('cache')) {
    /**
     * Get the cache manager instance or get/set cache values
     */
    function cache(?string $key = null, mixed $value = null, int $ttl = 3600): mixed
    {
        $cache = app('cache');

        if ($key === null) {
            return $cache;
        }

        if ($value === null) {
            return $cache->get($key);
        }

        return $cache->put($key, $value, $ttl);
    }
}

if (!function_exists('cache_get')) {
    /**
     * Get an item from the cache
     */
    function cache_get(string $key, mixed $default = null): mixed
    {
        return cache()->get($key, $default);
    }
}

if (!function_exists('cache_put')) {
    /**
     * Store an item in the cache
     */
    function cache_put(string $key, mixed $value, int $ttl = 3600): bool
    {
        return cache()->put($key, $value, $ttl);
    }
}

if (!function_exists('cache_forever')) {
    /**
     * Store an item in the cache indefinitely
     */
    function cache_forever(string $key, mixed $value): bool
    {
        return cache()->forever($key, $value);
    }
}

if (!function_exists('cache_remember')) {
    /**
     * Get an item from the cache, or store the default value
     */
    function cache_remember(string $key, int $ttl, callable $callback): mixed
    {
        return cache()->remember($key, $ttl, $callback);
    }
}

if (!function_exists('cache_remember_forever')) {
    /**
     * Get an item from the cache, or store the default value indefinitely
     */
    function cache_remember_forever(string $key, callable $callback): mixed
    {
        return cache()->rememberForever($key, $callback);
    }
}

if (!function_exists('cache_forget')) {
    /**
     * Remove an item from the cache
     */
    function cache_forget(string $key): bool
    {
        return cache()->forget($key);
    }
}

if (!function_exists('cache_flush')) {
    /**
     * Remove all items from the cache
     */
    function cache_flush(): bool
    {
        return cache()->flush();
    }
}

if (!function_exists('cache_has')) {
    /**
     * Determine if an item exists in the cache
     */
    function cache_has(string $key): bool
    {
        return cache()->has($key);
    }
}

if (!function_exists('cache_increment')) {
    /**
     * Increment the value of an item in the cache
     */
    function cache_increment(string $key, int $value = 1): int|false
    {
        return cache()->increment($key, $value);
    }
}

if (!function_exists('cache_decrement')) {
    /**
     * Decrement the value of an item in the cache
     */
    function cache_decrement(string $key, int $value = 1): int|false
    {
        return cache()->decrement($key, $value);
    }
}

if (!function_exists('cache_many')) {
    /**
     * Retrieve multiple items from the cache by key
     */
    function cache_many(array $keys): array
    {
        return cache()->many($keys);
    }
}

if (!function_exists('cache_put_many')) {
    /**
     * Store multiple items in the cache
     */
    function cache_put_many(array $values, int $ttl = 3600): bool
    {
        return cache()->putMany($values, $ttl);
    }
}

if (!function_exists('cache_tags')) {
    /**
     * Create a tagged cache instance (Redis only)
     */
    function cache_tags(array $tags): mixed
    {
        $cache = cache()->store();
        
        if (method_exists($cache, 'tags')) {
            return $cache->tags($tags);
        }
        
        throw new \RuntimeException('Cache tagging is only supported by Redis driver');
    }
}

if (!function_exists('cache_prefix')) {
    /**
     * Get the cache key prefix
     */
    function cache_prefix(): string
    {
        return cache()->getPrefix();
    }
}
