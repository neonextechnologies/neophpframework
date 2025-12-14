<?php

declare(strict_types=1);

namespace NeoCore\Broadcasting;

/**
 * Null Broadcaster
 * 
 * Does nothing (for testing or when broadcasting is disabled)
 */
class NullBroadcaster implements BroadcasterInterface
{
    protected array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Broadcast event (no-op)
     */
    public function broadcast(string $channel, string $event, array $data = []): void
    {
        // Do nothing
    }

    /**
     * Get channel name
     */
    public function getChannelName(string $channel): string
    {
        return $channel;
    }
}
