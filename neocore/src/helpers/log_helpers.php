<?php

declare(strict_types=1);

use NeoCore\Logging\LoggerInterface;
use NeoCore\Logging\LogLevel;
use NeoCore\Logging\AuditLogger;
use NeoCore\Monitoring\PerformanceMonitor;
use NeoCore\Monitoring\ErrorTracker;

if (!function_exists('logger')) {
    /**
     * Get logger instance or log a message
     */
    function logger(?string $message = null, array $context = [], LogLevel $level = LogLevel::INFO): LoggerInterface|null
    {
        $logger = app(LoggerInterface::class);

        if ($message === null) {
            return $logger;
        }

        $logger->log($level, $message, $context);
        return null;
    }
}

if (!function_exists('log_debug')) {
    /**
     * Log a debug message
     */
    function log_debug(string $message, array $context = []): void
    {
        logger($message, $context, LogLevel::DEBUG);
    }
}

if (!function_exists('log_info')) {
    /**
     * Log an info message
     */
    function log_info(string $message, array $context = []): void
    {
        logger($message, $context, LogLevel::INFO);
    }
}

if (!function_exists('log_notice')) {
    /**
     * Log a notice message
     */
    function log_notice(string $message, array $context = []): void
    {
        logger($message, $context, LogLevel::NOTICE);
    }
}

if (!function_exists('log_warning')) {
    /**
     * Log a warning message
     */
    function log_warning(string $message, array $context = []): void
    {
        logger($message, $context, LogLevel::WARNING);
    }
}

if (!function_exists('log_error')) {
    /**
     * Log an error message
     */
    function log_error(string $message, array $context = []): void
    {
        logger($message, $context, LogLevel::ERROR);
    }
}

if (!function_exists('log_critical')) {
    /**
     * Log a critical message
     */
    function log_critical(string $message, array $context = []): void
    {
        logger($message, $context, LogLevel::CRITICAL);
    }
}

if (!function_exists('log_alert')) {
    /**
     * Log an alert message
     */
    function log_alert(string $message, array $context = []): void
    {
        logger($message, $context, LogLevel::ALERT);
    }
}

if (!function_exists('log_emergency')) {
    /**
     * Log an emergency message
     */
    function log_emergency(string $message, array $context = []): void
    {
        logger($message, $context, LogLevel::EMERGENCY);
    }
}

if (!function_exists('audit')) {
    /**
     * Get audit logger instance
     */
    function audit(): AuditLogger
    {
        return app(AuditLogger::class);
    }
}

if (!function_exists('performance')) {
    /**
     * Get performance monitor instance
     */
    function performance(): PerformanceMonitor
    {
        return app(PerformanceMonitor::class);
    }
}

if (!function_exists('error_tracker')) {
    /**
     * Get error tracker instance
     */
    function error_tracker(): ErrorTracker
    {
        return app(ErrorTracker::class);
    }
}

if (!function_exists('measure')) {
    /**
     * Measure execution time
     */
    function measure(string $name, callable $callback): mixed
    {
        return performance()->measure($name, $callback);
    }
}

if (!function_exists('log_exception')) {
    /**
     * Log an exception
     */
    function log_exception(Throwable $exception, array $context = []): void
    {
        error_tracker()->capture($exception, $context);
    }
}

if (!function_exists('log_slow_query')) {
    /**
     * Log a slow query
     */
    function log_slow_query(string $query, float $duration, array $bindings = []): void
    {
        performance()->logSlowQuery($query, $duration, $bindings);
    }
}

if (!function_exists('log_memory')) {
    /**
     * Log memory usage
     */
    function log_memory(string $checkpoint = 'memory'): void
    {
        performance()->logMemoryUsage($checkpoint);
    }
}
