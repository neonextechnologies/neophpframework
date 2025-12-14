<?php

declare(strict_types=1);

namespace NeoCore\Providers;

use NeoCore\Container\ServiceProvider;
use NeoCore\Logging\LogManager;
use NeoCore\Logging\LoggerInterface;
use NeoCore\Logging\AuditLogger;
use NeoCore\Monitoring\PerformanceMonitor;
use NeoCore\Monitoring\ErrorTracker;

class LogServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register Log Manager
        $this->container->singleton(LogManager::class, function ($container) {
            return new LogManager($container->get('config')['logging'] ?? []);
        });

        // Bind LoggerInterface to Log Manager
        $this->container->bind(LoggerInterface::class, function ($container) {
            return $container->get(LogManager::class);
        });

        // Register Audit Logger
        $this->container->singleton(AuditLogger::class, function ($container) {
            return new AuditLogger($container->get('entityManager'));
        });

        // Register Performance Monitor
        $this->container->singleton(PerformanceMonitor::class, function ($container) {
            $config = $container->get('config')['logging']['performance'] ?? [];

            return new PerformanceMonitor(
                logger: $container->get(LoggerInterface::class),
                slowQueryThreshold: $config['slow_query_threshold'] ?? 1.0,
                slowRequestThreshold: $config['slow_request_threshold'] ?? 3.0
            );
        });

        // Register Error Tracker
        $this->container->singleton(ErrorTracker::class, function ($container) {
            $errorTracker = new ErrorTracker($container->get(LoggerInterface::class));

            $config = $container->get('config')['logging']['error_tracking'] ?? [];

            if (!empty($config['ignored_exceptions'])) {
                $errorTracker->ignore($config['ignored_exceptions']);
            }

            $errorTracker->addDefaultContextProviders();

            return $errorTracker;
        });
    }

    public function boot(): void
    {
        // Set error handler
        $errorTracker = $this->container->get(ErrorTracker::class);

        set_error_handler(function ($severity, $message, $file, $line) use ($errorTracker) {
            if (!(error_reporting() & $severity)) {
                return;
            }

            $errorTracker->capture(
                new \ErrorException($message, 0, $severity, $file, $line)
            );
        });

        // Set exception handler
        set_exception_handler(function (\Throwable $exception) use ($errorTracker) {
            $errorTracker->capture($exception);

            // Re-throw to allow other handlers
            throw $exception;
        });
    }
}
