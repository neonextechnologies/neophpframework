<?php

declare(strict_types=1);

namespace NeoCore\Database;

/**
 * Query Logger
 * 
 * Logs database queries for performance monitoring
 */
class QueryLogger
{
    protected array $queries = [];
    protected bool $enabled = true;
    protected float $slowQueryThreshold = 100.0; // milliseconds

    /**
     * Log a query
     */
    public function log(string $query, array $bindings = [], float $time = 0.0): void
    {
        if (!$this->enabled) {
            return;
        }

        $this->queries[] = [
            'query' => $query,
            'bindings' => $bindings,
            'time' => $time,
            'timestamp' => microtime(true),
            'slow' => $time > $this->slowQueryThreshold,
        ];
    }

    /**
     * Get all logged queries
     */
    public function getQueries(): array
    {
        return $this->queries;
    }

    /**
     * Get slow queries
     */
    public function getSlowQueries(): array
    {
        return array_filter($this->queries, fn($query) => $query['slow']);
    }

    /**
     * Get total query time
     */
    public function getTotalTime(): float
    {
        return array_sum(array_column($this->queries, 'time'));
    }

    /**
     * Get query count
     */
    public function count(): int
    {
        return count($this->queries);
    }

    /**
     * Clear logged queries
     */
    public function clear(): void
    {
        $this->queries = [];
    }

    /**
     * Enable query logging
     */
    public function enable(): void
    {
        $this->enabled = true;
    }

    /**
     * Disable query logging
     */
    public function disable(): void
    {
        $this->enabled = false;
    }

    /**
     * Check if logging is enabled
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Set slow query threshold
     */
    public function setSlowQueryThreshold(float $milliseconds): void
    {
        $this->slowQueryThreshold = $milliseconds;
    }

    /**
     * Get slow query threshold
     */
    public function getSlowQueryThreshold(): float
    {
        return $this->slowQueryThreshold;
    }

    /**
     * Format query for display
     */
    public function formatQuery(array $query): string
    {
        $sql = $query['query'];
        
        foreach ($query['bindings'] as $binding) {
            $value = is_string($binding) ? "'{$binding}'" : $binding;
            $sql = preg_replace('/\?/', (string) $value, $sql, 1);
        }
        
        return $sql;
    }

    /**
     * Get query statistics
     */
    public function getStatistics(): array
    {
        $queries = $this->getQueries();
        
        if (empty($queries)) {
            return [
                'total' => 0,
                'slow' => 0,
                'total_time' => 0.0,
                'average_time' => 0.0,
                'slowest' => null,
            ];
        }

        $times = array_column($queries, 'time');
        
        return [
            'total' => count($queries),
            'slow' => count($this->getSlowQueries()),
            'total_time' => array_sum($times),
            'average_time' => array_sum($times) / count($times),
            'slowest' => max($times),
        ];
    }

    /**
     * Dump queries to output
     */
    public function dump(): void
    {
        echo "=== Query Log ===\n";
        echo "Total Queries: " . $this->count() . "\n";
        echo "Total Time: " . number_format($this->getTotalTime(), 2) . "ms\n";
        echo "Slow Queries: " . count($this->getSlowQueries()) . "\n\n";

        foreach ($this->queries as $index => $query) {
            $marker = $query['slow'] ? '⚠️ SLOW' : '✓';
            echo sprintf(
                "%s Query #%d [%sms]\n%s\n\n",
                $marker,
                $index + 1,
                number_format($query['time'], 2),
                $this->formatQuery($query)
            );
        }
    }
}
