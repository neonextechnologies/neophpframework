<?php

declare(strict_types=1);

namespace NeoCore\Http\Api\Middleware;

use NeoCore\Http\Request;
use NeoCore\Http\Response;
use NeoCore\Http\Api\ApiResponse;
use Closure;

/**
 * API Rate Limiting Middleware
 * 
 * Limit API requests per user/IP
 */
class ApiRateLimit
{
    protected int $maxAttempts;
    protected int $decayMinutes;
    protected string $prefix;

    public function __construct(int $maxAttempts = 60, int $decayMinutes = 1, string $prefix = 'api')
    {
        $this->maxAttempts = $maxAttempts;
        $this->decayMinutes = $decayMinutes;
        $this->prefix = $prefix;
    }

    public function handle(Request $request, Closure $next): Response
    {
        $key = $this->resolveRequestSignature($request);

        $attempts = (int) cache_get($key, 0);

        if ($attempts >= $this->maxAttempts) {
            $retryAfter = $this->getTimeUntilNextRetry($key);
            return ApiResponse::rateLimitExceeded($retryAfter);
        }

        // Increment attempts
        cache_put($key, $attempts + 1, $this->decayMinutes * 60);

        $response = $next($request);

        // Add rate limit headers
        return $this->addHeaders(
            $response,
            $this->maxAttempts,
            $this->calculateRemainingAttempts($key, $this->maxAttempts)
        );
    }

    /**
     * Resolve request signature
     */
    protected function resolveRequestSignature(Request $request): string
    {
        $user = $request->user();
        
        if ($user) {
            return $this->prefix . ':' . $user->id;
        }

        return $this->prefix . ':' . $request->ip();
    }

    /**
     * Calculate remaining attempts
     */
    protected function calculateRemainingAttempts(string $key, int $maxAttempts): int
    {
        $attempts = (int) cache_get($key, 0);
        return max(0, $maxAttempts - $attempts);
    }

    /**
     * Get time until next retry
     */
    protected function getTimeUntilNextRetry(string $key): int
    {
        // Get TTL from cache
        $cache = cache()->store();
        
        if (method_exists($cache, 'ttl')) {
            return max(0, $cache->ttl($key));
        }

        return $this->decayMinutes * 60;
    }

    /**
     * Add rate limit headers to response
     */
    protected function addHeaders(Response $response, int $maxAttempts, int $remainingAttempts): Response
    {
        $response->header('X-RateLimit-Limit', (string) $maxAttempts);
        $response->header('X-RateLimit-Remaining', (string) $remainingAttempts);

        return $response;
    }
}
