<?php

declare(strict_types=1);

namespace NeoCore\Broadcasting;

/**
 * Pusher Broadcaster
 * 
 * Broadcasts events via Pusher service
 */
class PusherBroadcaster implements BroadcasterInterface
{
    protected array $config;
    protected $pusher;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->setupPusher();
    }

    /**
     * Setup Pusher instance
     */
    protected function setupPusher(): void
    {
        if (!class_exists('Pusher\Pusher')) {
            throw new \RuntimeException('Pusher PHP SDK not installed. Run: composer require pusher/pusher-php-server');
        }

        $options = $this->config['options'] ?? [];

        $this->pusher = new \Pusher\Pusher(
            $this->config['key'],
            $this->config['secret'],
            $this->config['app_id'],
            $options
        );
    }

    /**
     * Broadcast event
     */
    public function broadcast(string $channel, string $event, array $data = []): void
    {
        $this->pusher->trigger($channel, $event, $data);
    }

    /**
     * Broadcast to multiple channels
     */
    public function broadcastToChannels(array $channels, string $event, array $data = []): void
    {
        $this->pusher->trigger($channels, $event, $data);
    }

    /**
     * Get channel name
     */
    public function getChannelName(string $channel): string
    {
        return $channel;
    }

    /**
     * Authenticate private channel
     */
    public function authenticatePrivateChannel(string $socketId, string $channel): string
    {
        return $this->pusher->socket_auth($channel, $socketId);
    }

    /**
     * Authenticate presence channel
     */
    public function authenticatePresenceChannel(string $socketId, string $channel, array $userData): string
    {
        return $this->pusher->presence_auth($channel, $socketId, null, $userData);
    }
}
