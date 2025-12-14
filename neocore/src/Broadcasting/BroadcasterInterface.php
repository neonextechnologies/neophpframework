<?php

declare(strict_types=1);

namespace NeoCore\Broadcasting;

/**
 * Broadcaster Interface
 * 
 * Interface for broadcasting implementations
 */
interface BroadcasterInterface
{
    /**
     * Broadcast event to channel
     */
    public function broadcast(string $channel, string $event, array $data = []): void;

    /**
     * Get channel name with prefix
     */
    public function getChannelName(string $channel): string;
}
