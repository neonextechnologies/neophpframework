<?php

declare(strict_types=1);

namespace NeoCore\Cache;

use NeoCore\Container\Container;

/**
 * Cache Manager
 * 
 * Manages multiple cache stores
 */
class CacheManager
{
    protected Container $container;
    protected array $config;
    protected array $stores = [];

    public function __construct(Container $container, array $config = [])
    {
        $this->container = $container;
        $this->config = $config;
    }

    /**
     * Get a cache store instance
     */
    public function store(?string $name = null): CacheInterface
    {
        $name = $name ?? $this->getDefaultStore();

        if (!isset($this->stores[$name])) {
            $this->stores[$name] = $this->resolve($name);
        }

        return $this->stores[$name];
    }

    /**
     * Resolve a cache store instance
     */
    protected function resolve(string $name): CacheInterface
    {
        $config = $this->getConfig($name);
        $driver = $config['driver'] ?? 'file';

        $method = 'create' . ucfirst($driver) . 'Driver';

        if (method_exists($this, $method)) {
            return $this->$method($config);
        }

        throw new \InvalidArgumentException("Cache driver [{$driver}] is not supported.");
    }

    /**
     * Create file driver
     */
    protected function createFileDriver(array $config): CacheInterface
    {
        return new FileCache(
            $config['path'],
            $this->config['prefix'] ?? 'neocore_cache'
        );
    }

    /**
     * Create redis driver
     */
    protected function createRedisDriver(array $config): CacheInterface
    {
        $connection = $config['connection'] ?? 'default';
        $redisConfig = $this->config['redis'][$connection] ?? $this->config['redis']['default'];

        $redis = new \Redis();
        $redis->connect(
            $redisConfig['host'],
            $redisConfig['port']
        );

        if (!empty($redisConfig['password'])) {
            $redis->auth($redisConfig['password']);
        }

        if (isset($redisConfig['database'])) {
            $redis->select($redisConfig['database']);
        }

        return new RedisCache(
            $redis,
            $this->config['prefix'] ?? 'neocore_cache'
        );
    }

    /**
     * Create memcached driver
     */
    protected function createMemcachedDriver(array $config): CacheInterface
    {
        $memcached = new \Memcached();

        foreach ($config['servers'] as $server) {
            $memcached->addServer(
                $server['host'],
                $server['port'],
                $server['weight'] ?? 0
            );
        }

        return new MemcachedCache(
            $memcached,
            $this->config['prefix'] ?? 'neocore_cache'
        );
    }

    /**
     * Create array driver (for testing)
     */
    protected function createArrayDriver(array $config): CacheInterface
    {
        return new ArrayCache(
            $this->config['prefix'] ?? 'neocore_cache'
        );
    }

    /**
     * Get the configuration for a store
     */
    protected function getConfig(string $name): array
    {
        if (!isset($this->config['stores'][$name])) {
            throw new \InvalidArgumentException("Cache store [{$name}] is not configured.");
        }

        return $this->config['stores'][$name];
    }

    /**
     * Get the default store name
     */
    protected function getDefaultStore(): string
    {
        return $this->config['default'] ?? 'file';
    }

    /**
     * Dynamically call the default driver instance
     */
    public function __call(string $method, array $parameters): mixed
    {
        return $this->store()->$method(...$parameters);
    }
}
