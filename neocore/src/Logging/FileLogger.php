<?php

declare(strict_types=1);

namespace NeoCore\Logging;

/**
 * File Logger
 */
class FileLogger extends AbstractLogger
{
    protected string $path;

    public function __construct(string $path, LogLevel $level = LogLevel::DEBUG)
    {
        parent::__construct($level);
        $this->path = $path;
        $this->ensureDirectoryExists();
    }

    protected function write(LogLevel $level, string $message, array $context = []): void
    {
        $formatted = $this->formatMessage($level, $message, $context);

        // Add context details if present
        if (!empty($context)) {
            $formatted .= ' ' . json_encode($context);
        }

        $formatted .= PHP_EOL;

        file_put_contents($this->path, $formatted, FILE_APPEND | LOCK_EX);
    }

    protected function ensureDirectoryExists(): void
    {
        $directory = dirname($this->path);

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
    }

    /**
     * Clear log file
     */
    public function clear(): bool
    {
        if (file_exists($this->path)) {
            return unlink($this->path);
        }

        return true;
    }

    /**
     * Get log file size
     */
    public function size(): int
    {
        if (file_exists($this->path)) {
            return filesize($this->path);
        }

        return 0;
    }

    /**
     * Read log file
     */
    public function read(int $lines = null): string
    {
        if (!file_exists($this->path)) {
            return '';
        }

        $content = file_get_contents($this->path);

        if ($lines !== null) {
            $allLines = explode(PHP_EOL, $content);
            $content = implode(PHP_EOL, array_slice($allLines, -$lines));
        }

        return $content;
    }
}
