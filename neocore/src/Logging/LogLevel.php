<?php

declare(strict_types=1);

namespace NeoCore\Logging;

/**
 * Log Level
 */
enum LogLevel: string
{
    case EMERGENCY = 'emergency';
    case ALERT = 'alert';
    case CRITICAL = 'critical';
    case ERROR = 'error';
    case WARNING = 'warning';
    case NOTICE = 'notice';
    case INFO = 'info';
    case DEBUG = 'debug';

    /**
     * Get numeric severity (higher = more severe)
     */
    public function severity(): int
    {
        return match ($this) {
            self::EMERGENCY => 800,
            self::ALERT => 700,
            self::CRITICAL => 600,
            self::ERROR => 500,
            self::WARNING => 400,
            self::NOTICE => 300,
            self::INFO => 200,
            self::DEBUG => 100,
        };
    }

    /**
     * Check if this level is more severe than another
     */
    public function isMoreSevereThan(LogLevel $other): bool
    {
        return $this->severity() > $other->severity();
    }

    /**
     * Check if this level is at least as severe as another
     */
    public function isAtLeast(LogLevel $other): bool
    {
        return $this->severity() >= $other->severity();
    }

    /**
     * Create from string
     */
    public static function fromString(string $level): self
    {
        return self::from(strtolower($level));
    }
}
