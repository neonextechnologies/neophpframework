<?php

declare(strict_types=1);

namespace NeoCore\Logging;

/**
 * Stack Logger
 * 
 * Logs to multiple channels
 */
class StackLogger extends AbstractLogger
{
    /** @var LoggerInterface[] */
    protected array $channels = [];

    /**
     * @param LoggerInterface[] $channels
     */
    public function __construct(array $channels, LogLevel $level = LogLevel::DEBUG)
    {
        parent::__construct($level);
        $this->channels = $channels;
    }

    protected function write(LogLevel $level, string $message, array $context = []): void
    {
        foreach ($this->channels as $channel) {
            $channel->log($level, $message, $context);
        }
    }

    /**
     * Add a channel
     */
    public function addChannel(LoggerInterface $channel): self
    {
        $this->channels[] = $channel;
        return $this;
    }

    /**
     * Get channels
     */
    public function getChannels(): array
    {
        return $this->channels;
    }
}
