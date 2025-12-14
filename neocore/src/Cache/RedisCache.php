<?php

declare(strict_types=1);

namespace NeoCore\Cache;

class RedisCache extends AbstractCache
{
    protected \Redis $redis;

    public function __construct(\Redis $redis, string $prefix = 'cache')
    {
        parent::__construct($prefix);
        $this->redis = $redis;
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->redis->get($this->getKey($key));

        if ($value === false) {
            return $default;
        }

        return $this->unserialize($value);
    }

    /**
     * {@inheritdoc}
     */
    public function put(string $key, mixed $value, int $ttl = 3600): bool
    {
        $serialized = $this->serialize($value);

        if ($ttl > 0) {
            return $this->redis->setex($this->getKey($key), $ttl, $serialized);
        }

        return $this->redis->set($this->getKey($key), $serialized);
    }

    /**
     * {@inheritdoc}
     */
    public function forever(string $key, mixed $value): bool
    {
        return $this->redis->set($this->getKey($key), $this->serialize($value));
    }

    /**
     * {@inheritdoc}
     */
    public function increment(string $key, int $value = 1): int|false
    {
        return $this->redis->incrBy($this->getKey($key), $value);
    }

    /**
     * {@inheritdoc}
     */
    public function decrement(string $key, int $value = 1): int|false
    {
        return $this->redis->decrBy($this->getKey($key), $value);
    }

    /**
     * {@inheritdoc}
     */
    public function forget(string $key): bool
    {
        return $this->redis->del($this->getKey($key)) > 0;
    }

    /**
     * {@inheritdoc}
     */
    public function flush(): bool
    {
        return $this->redis->flushDB();
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $key): bool
    {
        return $this->redis->exists($this->getKey($key)) > 0;
    }

    /**
     * {@inheritdoc}
     */
    public function many(array $keys): array
    {
        $prefixedKeys = array_map(fn($key) => $this->getKey($key), $keys);
        $values = $this->redis->mGet($prefixedKeys);

        $result = [];
        foreach ($keys as $i => $key) {
            $result[$key] = $values[$i] !== false ? $this->unserialize($values[$i]) : null;
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function putMany(array $values, int $ttl = 3600): bool
    {
        $pipe = $this->redis->multi(\Redis::PIPELINE);

        foreach ($values as $key => $value) {
            $serialized = $this->serialize($value);
            
            if ($ttl > 0) {
                $pipe->setex($this->getKey($key), $ttl, $serialized);
            } else {
                $pipe->set($this->getKey($key), $serialized);
            }
        }

        $results = $pipe->exec();

        return !in_array(false, $results, true);
    }

    /**
     * Get keys matching a pattern
     */
    public function keys(string $pattern = '*'): array
    {
        return $this->redis->keys($this->getKey($pattern));
    }

    /**
     * Get cache statistics
     */
    public function getStats(): array
    {
        $info = $this->redis->info();
        
        return [
            'hits' => $info['keyspace_hits'] ?? 0,
            'misses' => $info['keyspace_misses'] ?? 0,
            'keys' => $this->redis->dbSize(),
            'memory' => $info['used_memory_human'] ?? 'N/A',
        ];
    }

    /**
     * Set expire time for a key
     */
    public function expire(string $key, int $ttl): bool
    {
        return $this->redis->expire($this->getKey($key), $ttl);
    }

    /**
     * Get remaining TTL for a key
     */
    public function ttl(string $key): int
    {
        return $this->redis->ttl($this->getKey($key));
    }

    /**
     * Add tags to cache items (Redis 5.0+)
     */
    public function tags(array $tags): TaggedCache
    {
        return new TaggedCache($this->redis, $this->prefix, $tags);
    }
}
