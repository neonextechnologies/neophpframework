<?php

declare(strict_types=1);

namespace NeoCore\Logging;

/**
 * Daily File Logger
 * 
 * Creates a new log file each day
 */
class DailyFileLogger extends AbstractLogger
{
    protected string $path;
    protected int $days;

    public function __construct(string $path, LogLevel $level = LogLevel::DEBUG, int $days = 14)
    {
        parent::__construct($level);
        $this->path = $path;
        $this->days = $days;
        $this->ensureDirectoryExists();
    }

    protected function write(LogLevel $level, string $message, array $context = []): void
    {
        $formatted = $this->formatMessage($level, $message, $context);

        if (!empty($context)) {
            $formatted .= ' ' . json_encode($context);
        }

        $formatted .= PHP_EOL;

        file_put_contents($this->getCurrentLogPath(), $formatted, FILE_APPEND | LOCK_EX);

        // Clean old logs
        $this->cleanOldLogs();
    }

    /**
     * Get current log file path
     */
    protected function getCurrentLogPath(): string
    {
        $date = date('Y-m-d');
        $directory = dirname($this->path);
        $filename = pathinfo($this->path, PATHINFO_FILENAME);
        $extension = pathinfo($this->path, PATHINFO_EXTENSION);

        return "{$directory}/{$filename}-{$date}.{$extension}";
    }

    /**
     * Clean old log files
     */
    protected function cleanOldLogs(): void
    {
        $directory = dirname($this->path);
        $filename = pathinfo($this->path, PATHINFO_FILENAME);
        $extension = pathinfo($this->path, PATHINFO_EXTENSION);

        $files = glob("{$directory}/{$filename}-*.{$extension}");

        if (!$files) {
            return;
        }

        $cutoff = strtotime("-{$this->days} days");

        foreach ($files as $file) {
            if (filemtime($file) < $cutoff) {
                unlink($file);
            }
        }
    }

    protected function ensureDirectoryExists(): void
    {
        $directory = dirname($this->path);

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
    }

    /**
     * Get log files
     */
    public function getLogFiles(): array
    {
        $directory = dirname($this->path);
        $filename = pathinfo($this->path, PATHINFO_FILENAME);
        $extension = pathinfo($this->path, PATHINFO_EXTENSION);

        $files = glob("{$directory}/{$filename}-*.{$extension}");

        if (!$files) {
            return [];
        }

        // Sort by date descending
        usort($files, function ($a, $b) {
            return filemtime($b) - filemtime($a);
        });

        return $files;
    }

    /**
     * Read log file for specific date
     */
    public function readDate(string $date): string
    {
        $directory = dirname($this->path);
        $filename = pathinfo($this->path, PATHINFO_FILENAME);
        $extension = pathinfo($this->path, PATHINFO_EXTENSION);

        $file = "{$directory}/{$filename}-{$date}.{$extension}";

        if (!file_exists($file)) {
            return '';
        }

        return file_get_contents($file);
    }
}
