<?php

declare(strict_types=1);

namespace NeoCore\Cache\Middleware;

use NeoCore\Http\Request;
use NeoCore\Http\Response;
use Closure;

/**
 * Cache Response Middleware
 * 
 * Caches HTTP responses for GET requests
 */
class CacheResponse
{
    protected int $ttl;
    protected array $excludePaths;

    public function __construct(int $ttl = 3600, array $excludePaths = [])
    {
        $this->ttl = $ttl;
        $this->excludePaths = $excludePaths;
    }

    public function handle(Request $request, Closure $next): Response
    {
        // Only cache GET requests
        if ($request->method() !== 'GET') {
            return $next($request);
        }

        // Check if path should be excluded
        foreach ($this->excludePaths as $path) {
            if (str_starts_with($request->path(), $path)) {
                return $next($request);
            }
        }

        $cacheKey = $this->getCacheKey($request);

        // Try to get cached response
        $cached = cache_get($cacheKey);

        if ($cached !== null) {
            return new Response($cached['body'], $cached['status'], $cached['headers']);
        }

        // Get fresh response
        $response = $next($request);

        // Cache successful responses
        if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
            cache_put($cacheKey, [
                'body' => $response->getContent(),
                'status' => $response->getStatusCode(),
                'headers' => $response->getHeaders(),
            ], $this->ttl);
        }

        return $response;
    }

    protected function getCacheKey(Request $request): string
    {
        return 'response:' . md5($request->path() . '?' . http_build_query($request->query()));
    }
}
