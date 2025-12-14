<?php

declare(strict_types=1);

namespace NeoCore\Middleware;

use NeoCore\Http\Request;
use NeoCore\Http\Response;
use NeoCore\Monitoring\PerformanceMonitor;
use Closure;

/**
 * Performance Monitoring Middleware
 * 
 * Tracks request performance
 */
class PerformanceMonitoringMiddleware
{
    protected PerformanceMonitor $monitor;

    public function __construct(PerformanceMonitor $monitor)
    {
        $this->monitor = $monitor;
    }

    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);

        $this->monitor->startTimer('request');

        $response = $next($request);

        $duration = $this->monitor->stopTimer('request');

        // Log slow requests
        $this->monitor->logSlowRequest(
            $request->getMethod(),
            $request->getUri(),
            $duration
        );

        // Add performance headers
        $response->header('X-Response-Time', number_format($duration * 1000, 2) . 'ms');
        $response->header('X-Memory-Usage', $this->formatBytes(memory_get_usage(true) - $startMemory));

        return $response;
    }

    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;

        while ($bytes > 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }
}
