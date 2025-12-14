<?php

declare(strict_types=1);

namespace NeoCore\Broadcasting;

/**
 * Broadcasting Manager
 * 
 * Manages event broadcasting to various channels
 */
class BroadcastManager
{
    protected array $drivers = [];
    protected array $config;
    protected ?string $defaultDriver = null;

    public function __construct(array $config = [])
    {
        $this->config = $config;
        $this->defaultDriver = $config['default'] ?? 'null';
    }

    /**
     * Get broadcaster instance
     */
    public function driver(?string $name = null): BroadcasterInterface
    {
        $name = $name ?? $this->defaultDriver;

        if (!isset($this->drivers[$name])) {
            $this->drivers[$name] = $this->createDriver($name);
        }

        return $this->drivers[$name];
    }

    /**
     * Create driver instance
     */
    protected function createDriver(string $name): BroadcasterInterface
    {
        $config = $this->config['connections'][$name] ?? [];
        $driver = $config['driver'] ?? 'null';

        return match($driver) {
            'pusher' => new PusherBroadcaster($config),
            'redis' => new RedisBroadcaster($config),
            'log' => new LogBroadcaster($config),
            default => new NullBroadcaster($config),
        };
    }

    /**
     * Broadcast event
     */
    public function broadcast(string $channel, string $event, array $data = []): void
    {
        $this->driver()->broadcast($channel, $event, $data);
    }

    /**
     * Broadcast to multiple channels
     */
    public function broadcastToChannels(array $channels, string $event, array $data = []): void
    {
        foreach ($channels as $channel) {
            $this->broadcast($channel, $event, $data);
        }
    }

    /**
     * Get default driver name
     */
    public function getDefaultDriver(): string
    {
        return $this->defaultDriver;
    }

    /**
     * Set default driver
     */
    public function setDefaultDriver(string $driver): void
    {
        $this->defaultDriver = $driver;
    }
}
