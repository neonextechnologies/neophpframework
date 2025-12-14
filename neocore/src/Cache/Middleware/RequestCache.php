<?php

declare(strict_types=1);

namespace NeoCore\Cache\Middleware;

use NeoCore\Http\Request;
use NeoCore\Http\Response;
use Closure;

/**
 * Request Cache Middleware
 * 
 * Caches expensive operation results per request
 */
class RequestCache
{
    protected array $cache = [];

    public function handle(Request $request, Closure $next): Response
    {
        // Store request cache in request attributes
        $request->attributes['request_cache'] = &$this->cache;

        return $next($request);
    }

    /**
     * Get an item from the request cache
     */
    public static function get(Request $request, string $key, mixed $default = null): mixed
    {
        $cache = $request->attributes['request_cache'] ?? [];
        return $cache[$key] ?? $default;
    }

    /**
     * Store an item in the request cache
     */
    public static function put(Request $request, string $key, mixed $value): void
    {
        if (isset($request->attributes['request_cache'])) {
            $request->attributes['request_cache'][$key] = $value;
        }
    }

    /**
     * Remember a value in the request cache
     */
    public static function remember(Request $request, string $key, callable $callback): mixed
    {
        $cache = $request->attributes['request_cache'] ?? [];

        if (isset($cache[$key])) {
            return $cache[$key];
        }

        $value = $callback();
        self::put($request, $key, $value);

        return $value;
    }
}
