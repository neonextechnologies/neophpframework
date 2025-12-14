<?php

declare(strict_types=1);

namespace NeoCore\Broadcasting;

/**
 * Log Broadcaster
 * 
 * Logs broadcast events (for debugging)
 */
class LogBroadcaster implements BroadcasterInterface
{
    protected array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Broadcast event
     */
    public function broadcast(string $channel, string $event, array $data = []): void
    {
        $message = sprintf(
            '[Broadcasting] Channel: %s, Event: %s, Data: %s',
            $channel,
            $event,
            json_encode($data)
        );

        error_log($message);
    }

    /**
     * Get channel name
     */
    public function getChannelName(string $channel): string
    {
        return $channel;
    }
}
