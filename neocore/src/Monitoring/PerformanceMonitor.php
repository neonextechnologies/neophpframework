<?php

declare(strict_types=1);

namespace NeoCore\Monitoring;

use NeoCore\Logging\LoggerInterface;
use NeoCore\Logging\LogLevel;

/**
 * Performance Monitor
 * 
 * Tracks application performance metrics
 */
class PerformanceMonitor
{
    protected LoggerInterface $logger;
    protected array $timers = [];
    protected array $counters = [];
    protected array $memory = [];
    protected float $slowQueryThreshold;
    protected float $slowRequestThreshold;

    public function __construct(
        LoggerInterface $logger,
        float $slowQueryThreshold = 1.0,
        float $slowRequestThreshold = 3.0
    ) {
        $this->logger = $logger;
        $this->slowQueryThreshold = $slowQueryThreshold;
        $this->slowRequestThreshold = $slowRequestThreshold;
    }

    /**
     * Start a timer
     */
    public function startTimer(string $name): void
    {
        $this->timers[$name] = [
            'start' => microtime(true),
            'memory_start' => memory_get_usage(true),
        ];
    }

    /**
     * Stop a timer
     */
    public function stopTimer(string $name): float
    {
        if (!isset($this->timers[$name])) {
            return 0.0;
        }

        $timer = $this->timers[$name];
        $duration = microtime(true) - $timer['start'];
        $memoryUsed = memory_get_usage(true) - $timer['memory_start'];

        $this->memory[$name] = $memoryUsed;

        unset($this->timers[$name]);

        return $duration;
    }

    /**
     * Measure execution time
     */
    public function measure(string $name, callable $callback): mixed
    {
        $this->startTimer($name);

        try {
            $result = $callback();
            return $result;
        } finally {
            $duration = $this->stopTimer($name);

            $this->logger->debug("Performance: {$name}", [
                'duration' => $duration,
                'memory' => $this->formatBytes($this->memory[$name] ?? 0),
            ]);
        }
    }

    /**
     * Increment a counter
     */
    public function increment(string $name, int $value = 1): void
    {
        $this->counters[$name] = ($this->counters[$name] ?? 0) + $value;
    }

    /**
     * Get counter value
     */
    public function getCounter(string $name): int
    {
        return $this->counters[$name] ?? 0;
    }

    /**
     * Log slow query
     */
    public function logSlowQuery(string $query, float $duration, array $bindings = []): void
    {
        if ($duration < $this->slowQueryThreshold) {
            return;
        }

        $this->logger->warning('Slow query detected', [
            'query' => $query,
            'duration' => $duration,
            'bindings' => $bindings,
            'threshold' => $this->slowQueryThreshold,
        ]);
    }

    /**
     * Log slow request
     */
    public function logSlowRequest(string $method, string $uri, float $duration): void
    {
        if ($duration < $this->slowRequestThreshold) {
            return;
        }

        $this->logger->warning('Slow request detected', [
            'method' => $method,
            'uri' => $uri,
            'duration' => $duration,
            'threshold' => $this->slowRequestThreshold,
        ]);
    }

    /**
     * Get memory usage
     */
    public function getMemoryUsage(): array
    {
        return [
            'current' => memory_get_usage(true),
            'peak' => memory_get_peak_usage(true),
            'current_formatted' => $this->formatBytes(memory_get_usage(true)),
            'peak_formatted' => $this->formatBytes(memory_get_peak_usage(true)),
        ];
    }

    /**
     * Log memory usage
     */
    public function logMemoryUsage(string $checkpoint = 'memory'): void
    {
        $usage = $this->getMemoryUsage();

        $this->logger->info("Memory usage at {$checkpoint}", $usage);
    }

    /**
     * Get all metrics
     */
    public function getMetrics(): array
    {
        return [
            'timers' => $this->timers,
            'counters' => $this->counters,
            'memory' => $this->getMemoryUsage(),
        ];
    }

    /**
     * Reset all metrics
     */
    public function reset(): void
    {
        $this->timers = [];
        $this->counters = [];
        $this->memory = [];
    }

    /**
     * Format bytes
     */
    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
