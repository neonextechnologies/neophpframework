<?php

declare(strict_types=1);

namespace NeoCore\Cache;

class MemcachedCache extends AbstractCache
{
    protected \Memcached $memcached;

    public function __construct(\Memcached $memcached, string $prefix = 'cache')
    {
        parent::__construct($prefix);
        $this->memcached = $memcached;
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->memcached->get($this->getKey($key));

        if ($this->memcached->getResultCode() === \Memcached::RES_NOTFOUND) {
            return $default;
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function put(string $key, mixed $value, int $ttl = 3600): bool
    {
        return $this->memcached->set($this->getKey($key), $value, $ttl);
    }

    /**
     * {@inheritdoc}
     */
    public function forever(string $key, mixed $value): bool
    {
        return $this->memcached->set($this->getKey($key), $value, 0);
    }

    /**
     * {@inheritdoc}
     */
    public function increment(string $key, int $value = 1): int|false
    {
        $result = $this->memcached->increment($this->getKey($key), $value);

        // If key doesn't exist, initialize it
        if ($result === false) {
            $this->put($key, $value);
            return $value;
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function decrement(string $key, int $value = 1): int|false
    {
        $result = $this->memcached->decrement($this->getKey($key), $value);

        // If key doesn't exist, initialize it
        if ($result === false) {
            $this->put($key, 0);
            return 0;
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function forget(string $key): bool
    {
        return $this->memcached->delete($this->getKey($key));
    }

    /**
     * {@inheritdoc}
     */
    public function flush(): bool
    {
        return $this->memcached->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $key): bool
    {
        $this->memcached->get($this->getKey($key));
        return $this->memcached->getResultCode() !== \Memcached::RES_NOTFOUND;
    }

    /**
     * {@inheritdoc}
     */
    public function many(array $keys): array
    {
        $prefixedKeys = array_map(fn($key) => $this->getKey($key), $keys);
        $values = $this->memcached->getMulti($prefixedKeys);

        $result = [];
        foreach ($keys as $key) {
            $prefixedKey = $this->getKey($key);
            $result[$key] = $values[$prefixedKey] ?? null;
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function putMany(array $values, int $ttl = 3600): bool
    {
        $prefixedValues = [];
        
        foreach ($values as $key => $value) {
            $prefixedValues[$this->getKey($key)] = $value;
        }

        return $this->memcached->setMulti($prefixedValues, $ttl);
    }

    /**
     * Get cache statistics
     */
    public function getStats(): array
    {
        return $this->memcached->getStats();
    }

    /**
     * Get server version
     */
    public function getVersion(): string
    {
        $version = $this->memcached->getVersion();
        return is_array($version) ? reset($version) : $version;
    }

    /**
     * Touch a key (update its expiration time)
     */
    public function touch(string $key, int $ttl): bool
    {
        return $this->memcached->touch($this->getKey($key), $ttl);
    }
}
