<?php

namespace NeoCore\System\CLI\Commands;

/**
 * Command - Base command class
 */
abstract class Command
{
    protected string $basePath;

    public function __construct(string $basePath)
    {
        $this->basePath = rtrim($basePath, '/');
    }

    /**
     * Execute command
     */
    abstract public function execute(array $args): int;

    /**
     * Get argument by index
     */
    protected function argument(array $args, int $index, $default = null)
    {
        return $args[$index] ?? $default;
    }

    /**
     * Write to console
     */
    protected function write(string $message): void
    {
        echo $message;
    }

    /**
     * Write line to console
     */
    protected function writeLine(string $message): void
    {
        echo $message . "\n";
    }

    /**
     * Write success message
     */
    protected function success(string $message): void
    {
        echo "[SUCCESS] $message\n";
    }

    /**
     * Write error message
     */
    protected function error(string $message): void
    {
        echo "[ERROR] $message\n";
    }

    /**
     * Write info message
     */
    protected function info(string $message): void
    {
        echo "[INFO] $message\n";
    }

    /**
     * Create directory if not exists
     */
    protected function ensureDirectory(string $path): void
    {
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
    }

    /**
     * Create file with content
     */
    protected function createFile(string $path, string $content): bool
    {
        $dir = dirname($path);
        $this->ensureDirectory($dir);
        
        if (file_exists($path)) {
            $this->error("File already exists: $path");
            return false;
        }

        return file_put_contents($path, $content) !== false;
    }
}
