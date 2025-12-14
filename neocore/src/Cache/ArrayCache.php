<?php

declare(strict_types=1);

namespace NeoCore\Cache;

/**
 * Array Cache (for testing)
 */
class ArrayCache extends AbstractCache
{
    protected array $storage = [];

    /**
     * {@inheritdoc}
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $key = $this->getKey($key);

        if (!isset($this->storage[$key])) {
            return $default;
        }

        $data = $this->storage[$key];

        // Check if expired
        if ($data['expires_at'] !== 0 && $data['expires_at'] < time()) {
            unset($this->storage[$key]);
            return $default;
        }

        return $data['value'];
    }

    /**
     * {@inheritdoc}
     */
    public function put(string $key, mixed $value, int $ttl = 3600): bool
    {
        $this->storage[$this->getKey($key)] = [
            'value' => $value,
            'expires_at' => $ttl > 0 ? time() + $ttl : 0,
        ];

        return true;
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
        unset($this->storage[$this->getKey($key)]);
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function flush(): bool
    {
        $this->storage = [];
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
     * Get all cached items
     */
    public function all(): array
    {
        return $this->storage;
    }

    /**
     * Get number of cached items
     */
    public function count(): int
    {
        return count($this->storage);
    }
}
