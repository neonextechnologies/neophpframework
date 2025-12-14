<?php

declare(strict_types=1);

namespace NeoCore\Logging;

abstract class AbstractLogger implements LoggerInterface
{
    protected LogLevel $level;

    public function __construct(LogLevel $level = LogLevel::DEBUG)
    {
        $this->level = $level;
    }

    /**
     * Write log message
     */
    abstract protected function write(LogLevel $level, string $message, array $context = []): void;

    /**
     * Check if level should be logged
     */
    protected function shouldLog(LogLevel $level): bool
    {
        return $level->isAtLeast($this->level);
    }

    /**
     * Format log message
     */
    protected function formatMessage(LogLevel $level, string $message, array $context = []): string
    {
        $timestamp = date('Y-m-d H:i:s');
        $levelName = strtoupper($level->value);
        
        // Replace placeholders in message
        foreach ($context as $key => $value) {
            $message = str_replace('{' . $key . '}', $this->stringify($value), $message);
        }

        return "[{$timestamp}] {$levelName}: {$message}";
    }

    /**
     * Convert value to string
     */
    protected function stringify(mixed $value): string
    {
        if (is_null($value)) {
            return 'null';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        if (is_array($value) || is_object($value)) {
            return json_encode($value);
        }

        return 'unknown';
    }

    public function emergency(string $message, array $context = []): void
    {
        $this->log('emergency', $message, $context);
    }

    public function alert(string $message, array $context = []): void
    {
        $this->log('alert', $message, $context);
    }

    public function critical(string $message, array $context = []): void
    {
        $this->log('critical', $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->log('warning', $message, $context);
    }

    public function notice(string $message, array $context = []): void
    {
        $this->log('notice', $message, $context);
    }

    public function info(string $message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }

    public function debug(string $message, array $context = []): void
    {
        $this->log('debug', $message, $context);
    }

    public function log(string $level, string $message, array $context = []): void
    {
        $logLevel = LogLevel::fromString($level);

        if (!$this->shouldLog($logLevel)) {
            return;
        }

        $this->write($logLevel, $message, $context);
    }
}
