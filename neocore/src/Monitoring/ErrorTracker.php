<?php

declare(strict_types=1);

namespace NeoCore\Monitoring;

use NeoCore\Logging\LoggerInterface;
use NeoCore\Logging\LogLevel;
use Throwable;

/**
 * Error Tracker
 * 
 * Tracks and logs errors with context
 */
class ErrorTracker
{
    protected LoggerInterface $logger;
    protected array $ignoredExceptions = [];
    protected array $contextProviders = [];

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Capture an exception
     */
    public function capture(Throwable $exception, array $additionalContext = []): void
    {
        if ($this->shouldIgnore($exception)) {
            return;
        }

        $level = $this->determineLogLevel($exception);
        $context = $this->buildContext($exception, $additionalContext);

        $this->logger->log(
            $level,
            $exception->getMessage(),
            $context
        );
    }

    /**
     * Capture an error
     */
    public function captureError(
        string $message,
        array $context = [],
        LogLevel $level = LogLevel::ERROR
    ): void {
        $context = array_merge($context, $this->getAdditionalContext());

        $this->logger->log($level, $message, $context);
    }

    /**
     * Set ignored exceptions
     */
    public function ignore(array $exceptionClasses): self
    {
        $this->ignoredExceptions = array_merge($this->ignoredExceptions, $exceptionClasses);
        return $this;
    }

    /**
     * Add context provider
     */
    public function addContextProvider(callable $provider): self
    {
        $this->contextProviders[] = $provider;
        return $this;
    }

    /**
     * Should ignore exception
     */
    protected function shouldIgnore(Throwable $exception): bool
    {
        foreach ($this->ignoredExceptions as $exceptionClass) {
            if ($exception instanceof $exceptionClass) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine log level
     */
    protected function determineLogLevel(Throwable $exception): LogLevel
    {
        // You can customize this based on exception types
        if ($exception instanceof \ErrorException) {
            return match ($exception->getSeverity()) {
                E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE => LogLevel::CRITICAL,
                E_WARNING, E_CORE_WARNING, E_COMPILE_WARNING => LogLevel::WARNING,
                E_NOTICE => LogLevel::NOTICE,
                default => LogLevel::ERROR,
            };
        }

        return LogLevel::ERROR;
    }

    /**
     * Build context
     */
    protected function buildContext(Throwable $exception, array $additionalContext = []): array
    {
        $context = [
            'exception' => $exception,
            'exception_class' => get_class($exception),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'code' => $exception->getCode(),
        ];

        if ($exception->getPrevious()) {
            $context['previous'] = [
                'class' => get_class($exception->getPrevious()),
                'message' => $exception->getPrevious()->getMessage(),
                'file' => $exception->getPrevious()->getFile(),
                'line' => $exception->getPrevious()->getLine(),
            ];
        }

        return array_merge(
            $context,
            $this->getAdditionalContext(),
            $additionalContext
        );
    }

    /**
     * Get additional context
     */
    protected function getAdditionalContext(): array
    {
        $context = [];

        foreach ($this->contextProviders as $provider) {
            $context = array_merge($context, $provider());
        }

        return $context;
    }

    /**
     * Add default context providers
     */
    public function addDefaultContextProviders(): self
    {
        // Request context
        $this->addContextProvider(function () {
            if (!isset($_SERVER['REQUEST_METHOD'])) {
                return [];
            }

            return [
                'request' => [
                    'method' => $_SERVER['REQUEST_METHOD'] ?? null,
                    'uri' => $_SERVER['REQUEST_URI'] ?? null,
                    'query' => $_GET ?? [],
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                ],
            ];
        });

        // System context
        $this->addContextProvider(function () {
            return [
                'system' => [
                    'php_version' => PHP_VERSION,
                    'memory_usage' => memory_get_usage(true),
                    'memory_peak' => memory_get_peak_usage(true),
                ],
            ];
        });

        return $this;
    }
}
