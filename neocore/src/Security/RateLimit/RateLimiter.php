<?php

declare(strict_types=1);

namespace NeoCore\Security\RateLimit;

use NeoCore\Cache\CacheInterface;

/**
 * Rate Limiter
 * 
 * Implements rate limiting using token bucket algorithm
 */
class RateLimiter
{
    protected CacheInterface $cache;
    protected int $maxAttempts = 60;
    protected int $decayMinutes = 1;

    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Determine if the given key has been "accessed" too many times
     */
    public function tooManyAttempts(string $key, int $maxAttempts): bool
    {
        if ($this->attempts($key) >= $maxAttempts) {
            if ($this->cache->has($key . ':timer')) {
                return true;
            }

            $this->resetAttempts($key);
        }

        return false;
    }

    /**
     * Increment the counter for a given key for a given decay time
     */
    public function hit(string $key, int $decayMinutes = 1): int
    {
        $this->cache->add($key . ':timer', time() + ($decayMinutes * 60), $decayMinutes * 60);

        $added = $this->cache->add($key, 0, $decayMinutes * 60);

        $hits = (int) $this->cache->increment($key);

        if (!$added && $hits == 1) {
            $this->cache->put($key, 1, $decayMinutes * 60);
        }

        return $hits;
    }

    /**
     * Get the number of attempts for the given key
     */
    public function attempts(string $key): int
    {
        return (int) $this->cache->get($key, 0);
    }

    /**
     * Reset the number of attempts for the given key
     */
    public function resetAttempts(string $key): void
    {
        $this->cache->forget($key);
    }

    /**
     * Get the number of retries left for the given key
     */
    public function retriesLeft(string $key, int $maxAttempts): int
    {
        $attempts = $this->attempts($key);
        return $maxAttempts - $attempts;
    }

    /**
     * Clear the hits and lockout timer for the given key
     */
    public function clear(string $key): void
    {
        $this->resetAttempts($key);
        $this->cache->forget($key . ':timer');
    }

    /**
     * Get the number of seconds until the "key" is accessible again
     */
    public function availableIn(string $key): int
    {
        return (int) $this->cache->get($key . ':timer', 0) - time();
    }
}
