<?php

declare(strict_types=1);

namespace NeoCore\Broadcasting;

/**
 * Redis Broadcaster
 * 
 * Broadcasts events via Redis pub/sub
 */
class RedisBroadcaster implements BroadcasterInterface
{
    protected array $config;
    protected $redis;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->setupRedis();
    }

    /**
     * Setup Redis connection
     */
    protected function setupRedis(): void
    {
        if (!extension_loaded('redis')) {
            throw new \RuntimeException('Redis extension not installed');
        }

        $this->redis = new \Redis();
        $this->redis->connect(
            $this->config['host'] ?? '127.0.0.1',
            $this->config['port'] ?? 6379
        );

        if (isset($this->config['password'])) {
            $this->redis->auth($this->config['password']);
        }

        if (isset($this->config['database'])) {
            $this->redis->select($this->config['database']);
        }
    }

    /**
     * Broadcast event
     */
    public function broadcast(string $channel, string $event, array $data = []): void
    {
        $payload = json_encode([
            'event' => $event,
            'data' => $data,
            'channel' => $channel,
        ]);

        $this->redis->publish($this->getChannelName($channel), $payload);
    }

    /**
     * Get channel name with prefix
     */
    public function getChannelName(string $channel): string
    {
        $prefix = $this->config['prefix'] ?? 'neocore';
        return "{$prefix}:{$channel}";
    }
}
