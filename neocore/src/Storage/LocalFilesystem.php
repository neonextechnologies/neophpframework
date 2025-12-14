<?php

declare(strict_types=1);

namespace NeoCore\Storage;

/**
 * Local Filesystem
 * 
 * Handles file operations on local disk
 */
class LocalFilesystem implements FilesystemInterface
{
    protected string $root;

    public function __construct(string $root)
    {
        $this->root = rtrim($root, '/\\');
    }

    /**
     * Check if a file exists
     */
    public function exists(string $path): bool
    {
        return file_exists($this->getFullPath($path));
    }

    /**
     * Get the contents of a file
     */
    public function get(string $path): ?string
    {
        $fullPath = $this->getFullPath($path);
        return file_exists($fullPath) ? file_get_contents($fullPath) : null;
    }

    /**
     * Write the contents to a file
     */
    public function put(string $path, string $contents, array $options = []): bool
    {
        $fullPath = $this->getFullPath($path);
        $this->ensureDirectoryExists(dirname($fullPath));
        
        $visibility = $options['visibility'] ?? 'private';
        $result = file_put_contents($fullPath, $contents) !== false;
        
        if ($result && $visibility === 'public') {
            chmod($fullPath, 0644);
        }
        
        return $result;
    }

    /**
     * Prepend to a file
     */
    public function prepend(string $path, string $data): bool
    {
        $fullPath = $this->getFullPath($path);
        
        if ($this->exists($path)) {
            return $this->put($path, $data . $this->get($path));
        }
        
        return $this->put($path, $data);
    }

    /**
     * Append to a file
     */
    public function append(string $path, string $data): bool
    {
        $fullPath = $this->getFullPath($path);
        return file_put_contents($fullPath, $data, FILE_APPEND) !== false;
    }

    /**
     * Delete a file
     */
    public function delete(string $path): bool
    {
        $fullPath = $this->getFullPath($path);
        
        if (!file_exists($fullPath)) {
            return false;
        }
        
        return unlink($fullPath);
    }

    /**
     * Delete a directory
     */
    public function deleteDirectory(string $directory): bool
    {
        $fullPath = $this->getFullPath($directory);
        
        if (!is_dir($fullPath)) {
            return false;
        }
        
        return $this->removeDirectory($fullPath);
    }

    /**
     * Copy a file to a new location
     */
    public function copy(string $from, string $to): bool
    {
        $fromPath = $this->getFullPath($from);
        $toPath = $this->getFullPath($to);
        
        $this->ensureDirectoryExists(dirname($toPath));
        
        return copy($fromPath, $toPath);
    }

    /**
     * Move a file to a new location
     */
    public function move(string $from, string $to): bool
    {
        $fromPath = $this->getFullPath($from);
        $toPath = $this->getFullPath($to);
        
        $this->ensureDirectoryExists(dirname($toPath));
        
        return rename($fromPath, $toPath);
    }

    /**
     * Get the file size
     */
    public function size(string $path): int
    {
        $fullPath = $this->getFullPath($path);
        return file_exists($fullPath) ? filesize($fullPath) : 0;
    }

    /**
     * Get the file's last modification time
     */
    public function lastModified(string $path): int
    {
        $fullPath = $this->getFullPath($path);
        return file_exists($fullPath) ? filemtime($fullPath) : 0;
    }

    /**
     * Get an array of all files in a directory
     */
    public function files(string $directory = ''): array
    {
        $fullPath = $this->getFullPath($directory);
        
        if (!is_dir($fullPath)) {
            return [];
        }
        
        $files = [];
        foreach (scandir($fullPath) as $file) {
            $filePath = $fullPath . DIRECTORY_SEPARATOR . $file;
            if (is_file($filePath)) {
                $files[] = $directory ? $directory . '/' . $file : $file;
            }
        }
        
        return $files;
    }

    /**
     * Get all directories within a directory
     */
    public function directories(string $directory = ''): array
    {
        $fullPath = $this->getFullPath($directory);
        
        if (!is_dir($fullPath)) {
            return [];
        }
        
        $directories = [];
        foreach (scandir($fullPath) as $dir) {
            if ($dir === '.' || $dir === '..') {
                continue;
            }
            
            $dirPath = $fullPath . DIRECTORY_SEPARATOR . $dir;
            if (is_dir($dirPath)) {
                $directories[] = $directory ? $directory . '/' . $dir : $dir;
            }
        }
        
        return $directories;
    }

    /**
     * Create a directory
     */
    public function makeDirectory(string $path): bool
    {
        $fullPath = $this->getFullPath($path);
        
        if (is_dir($fullPath)) {
            return true;
        }
        
        return mkdir($fullPath, 0755, true);
    }

    /**
     * Get the URL for the file
     */
    public function url(string $path): string
    {
        return '/' . ltrim($path, '/');
    }

    /**
     * Get the full path
     */
    protected function getFullPath(string $path): string
    {
        return $this->root . DIRECTORY_SEPARATOR . ltrim($path, '/\\');
    }

    /**
     * Ensure directory exists
     */
    protected function ensureDirectoryExists(string $directory): void
    {
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
    }

    /**
     * Recursively remove a directory
     */
    protected function removeDirectory(string $directory): bool
    {
        $items = scandir($directory);
        
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            
            $path = $directory . DIRECTORY_SEPARATOR . $item;
            
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }
        
        return rmdir($directory);
    }
}
