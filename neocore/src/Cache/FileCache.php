<?php

declare(strict_types=1);

namespace NeoCore\Cache;

class FileCache extends AbstractCache
{
    protected string $path;

    public function __construct(string $path, string $prefix = 'cache')
    {
        parent::__construct($prefix);
        $this->path = rtrim($path, DIRECTORY_SEPARATOR);

        if (!is_dir($this->path)) {
            mkdir($this->path, 0755, true);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $file = $this->getFilePath($key);

        if (!file_exists($file)) {
            return $default;
        }

        $contents = file_get_contents($file);
        
        if ($contents === false) {
            return $default;
        }

        $data = $this->unserialize($contents);

        // Check if expired
        if ($data['expires_at'] !== 0 && $data['expires_at'] < time()) {
            $this->forget($key);
            return $default;
        }

        return $data['value'];
    }

    /**
     * {@inheritdoc}
     */
    public function put(string $key, mixed $value, int $ttl = 3600): bool
    {
        $file = $this->getFilePath($key);
        $directory = dirname($file);

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $data = [
            'value' => $value,
            'expires_at' => $ttl > 0 ? time() + $ttl : 0,
        ];

        $result = file_put_contents($file, $this->serialize($data), LOCK_EX);

        return $result !== false;
    }

    /**
     * {@inheritdoc}
     */
    public function forever(string $key, mixed $value): bool
    {
        return $this->put($key, $value, 0);
    }

    /**
     * {@inheritdoc}
     */
    public function increment(string $key, int $value = 1): int|false
    {
        $current = (int) $this->get($key, 0);
        $new = $current + $value;

        if ($this->put($key, $new)) {
            return $new;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function decrement(string $key, int $value = 1): int|false
    {
        return $this->increment($key, -$value);
    }

    /**
     * {@inheritdoc}
     */
    public function forget(string $key): bool
    {
        $file = $this->getFilePath($key);

        if (file_exists($file)) {
            return unlink($file);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function flush(): bool
    {
        $this->deleteDirectory($this->path);
        mkdir($this->path, 0755, true);
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    /**
     * Get the file path for a key
     */
    protected function getFilePath(string $key): string
    {
        $hash = md5($this->getKey($key));
        $parts = [
            substr($hash, 0, 2),
            substr($hash, 2, 2),
        ];

        return $this->path . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $parts) . DIRECTORY_SEPARATOR . $hash;
    }

    /**
     * Recursively delete a directory
     */
    protected function deleteDirectory(string $directory): bool
    {
        if (!is_dir($directory)) {
            return false;
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($items as $item) {
            if ($item->isDir()) {
                rmdir($item->getRealPath());
            } else {
                unlink($item->getRealPath());
            }
        }

        return rmdir($directory);
    }

    /**
     * Clean up expired cache files
     */
    public function cleanup(): int
    {
        $deleted = 0;

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->path, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($items as $item) {
            if ($item->isFile()) {
                $contents = file_get_contents($item->getRealPath());
                
                if ($contents !== false) {
                    $data = $this->unserialize($contents);
                    
                    if ($data['expires_at'] !== 0 && $data['expires_at'] < time()) {
                        unlink($item->getRealPath());
                        $deleted++;
                    }
                }
            }
        }

        return $deleted;
    }
}
