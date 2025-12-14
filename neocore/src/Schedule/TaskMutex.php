<?php

declare(strict_types=1);

namespace NeoCore\Schedule;

/**
 * Task Mutex
 * 
 * Prevents task overlap using file-based locks
 */
class TaskMutex
{
    protected string $lockPath;

    public function __construct(?string $lockPath = null)
    {
        $this->lockPath = $lockPath ?? __DIR__ . '/../../storage/framework/schedule';
        
        if (!is_dir($this->lockPath)) {
            mkdir($this->lockPath, 0755, true);
        }
    }

    /**
     * Create a lock
     */
    public function create(string $key, int $expiresAt = 1440): bool
    {
        $lockFile = $this->getLockFile($key);

        // Check if lock exists and is not expired
        if (file_exists($lockFile)) {
            $createdAt = filemtime($lockFile);
            $expiresIn = $createdAt + ($expiresAt * 60);

            if (time() < $expiresIn) {
                return false; // Lock is still active
            }

            // Lock expired, remove it
            $this->forget($key);
        }

        // Create new lock
        return file_put_contents($lockFile, time()) !== false;
    }

    /**
     * Check if lock exists
     */
    public function exists(string $key): bool
    {
        return file_exists($this->getLockFile($key));
    }

    /**
     * Remove lock
     */
    public function forget(string $key): bool
    {
        $lockFile = $this->getLockFile($key);

        if (file_exists($lockFile)) {
            return unlink($lockFile);
        }

        return true;
    }

    /**
     * Get lock file path
     */
    protected function getLockFile(string $key): string
    {
        return $this->lockPath . '/' . $key . '.lock';
    }

    /**
     * Clear all locks
     */
    public function clearAll(): void
    {
        $files = glob($this->lockPath . '/*.lock');

        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }

    /**
     * Clear expired locks
     */
    public function clearExpired(int $maxAge = 1440): void
    {
        $files = glob($this->lockPath . '/*.lock');
        $now = time();

        foreach ($files as $file) {
            if (is_file($file)) {
                $createdAt = filemtime($file);
                $age = ($now - $createdAt) / 60; // Convert to minutes

                if ($age > $maxAge) {
                    unlink($file);
                }
            }
        }
    }
}
