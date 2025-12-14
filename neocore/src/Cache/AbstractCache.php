<?php

declare(strict_types=1);

namespace NeoCore\Cache;

abstract class AbstractCache implements CacheInterface
{
    protected string $prefix;

    public function __construct(string $prefix = 'cache')
    {
        $this->prefix = $prefix;
    }

    /**
     * Get the full cache key with prefix
     */
    protected function getKey(string $key): string
    {
        return $this->prefix . ':' . $key;
    }

    /**
     * {@inheritdoc}
     */
    public function remember(string $key, int $ttl, callable $callback): mixed
    {
        $value = $this->get($key);

        if ($value !== null) {
            return $value;
        }

        $value = $callback();
        $this->put($key, $value, $ttl);

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function rememberForever(string $key, callable $callback): mixed
    {
        $value = $this->get($key);

        if ($value !== null) {
            return $value;
        }

        $value = $callback();
        $this->forever($key, $value);

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function many(array $keys): array
    {
        $result = [];

        foreach ($keys as $key) {
            $result[$key] = $this->get($key);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function putMany(array $values, int $ttl = 3600): bool
    {
        $success = true;

        foreach ($values as $key => $value) {
            if (!$this->put($key, $value, $ttl)) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * {@inheritdoc}
     */
    public function getPrefix(): string
    {
        return $this->prefix;
    }

    /**
     * Serialize a value for storage
     */
    protected function serialize(mixed $value): string
    {
        return serialize($value);
    }

    /**
     * Unserialize a value from storage
     */
    protected function unserialize(string $value): mixed
    {
        return unserialize($value);
    }
}
