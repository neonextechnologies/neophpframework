<?php

declare(strict_types=1);

namespace NeoCore\Cache;

/**
 * Tagged Cache for Redis
 * 
 * Allows grouping cache items with tags for bulk operations
 */
class TaggedCache
{
    protected \Redis $redis;
    protected string $prefix;
    protected array $tags;

    public function __construct(\Redis $redis, string $prefix, array $tags)
    {
        $this->redis = $redis;
        $this->prefix = $prefix;
        $this->tags = $tags;
    }

    /**
     * Get an item from the cache
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->redis->get($this->getKey($key));

        if ($value === false) {
            return $default;
        }

        return unserialize($value);
    }

    /**
     * Store an item in the cache with tags
     */
    public function put(string $key, mixed $value, int $ttl = 3600): bool
    {
        $fullKey = $this->getKey($key);
        $serialized = serialize($value);

        // Store the value
        if ($ttl > 0) {
            $result = $this->redis->setex($fullKey, $ttl, $serialized);
        } else {
            $result = $this->redis->set($fullKey, $serialized);
        }

        // Add key to tag sets
        foreach ($this->tags as $tag) {
            $this->redis->sAdd($this->getTagKey($tag), $fullKey);
            
            if ($ttl > 0) {
                $this->redis->expire($this->getTagKey($tag), $ttl);
            }
        }

        return $result;
    }

    /**
     * Store an item indefinitely
     */
    public function forever(string $key, mixed $value): bool
    {
        return $this->put($key, $value, 0);
    }

    /**
     * Remove an item from the cache
     */
    public function forget(string $key): bool
    {
        $fullKey = $this->getKey($key);
        
        // Remove from tag sets
        foreach ($this->tags as $tag) {
            $this->redis->sRem($this->getTagKey($tag), $fullKey);
        }

        return $this->redis->del($fullKey) > 0;
    }

    /**
     * Remove all items with these tags
     */
    public function flush(): bool
    {
        $keys = [];

        // Get all keys for all tags
        foreach ($this->tags as $tag) {
            $tagKey = $this->getTagKey($tag);
            $tagKeys = $this->redis->sMembers($tagKey);
            
            if ($tagKeys) {
                $keys = array_merge($keys, $tagKeys);
            }
            
            // Delete the tag set
            $this->redis->del($tagKey);
        }

        // Delete all tagged keys
        if (!empty($keys)) {
            $this->redis->del(...array_unique($keys));
        }

        return true;
    }

    /**
     * Get the full cache key with prefix and tags
     */
    protected function getKey(string $key): string
    {
        $tagString = implode('|', $this->tags);
        return $this->prefix . ':tags:' . md5($tagString) . ':' . $key;
    }

    /**
     * Get the tag set key
     */
    protected function getTagKey(string $tag): string
    {
        return $this->prefix . ':tag:' . $tag;
    }
}
