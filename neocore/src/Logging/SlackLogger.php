<?php

declare(strict_types=1);

namespace NeoCore\Logging;

/**
 * Slack Logger
 * 
 * Sends logs to Slack via webhook
 */
class SlackLogger extends AbstractLogger
{
    protected string $webhookUrl;
    protected string $username;
    protected string $channel;
    protected ?string $iconEmoji;

    public function __construct(
        string $webhookUrl,
        string $username = 'NeoCore',
        string $channel = '#logs',
        ?string $iconEmoji = ':warning:',
        LogLevel $level = LogLevel::WARNING
    ) {
        parent::__construct($level);
        $this->webhookUrl = $webhookUrl;
        $this->username = $username;
        $this->channel = $channel;
        $this->iconEmoji = $iconEmoji;
    }

    protected function write(LogLevel $level, string $message, array $context = []): void
    {
        $payload = [
            'username' => $this->username,
            'channel' => $this->channel,
            'text' => $this->formatSlackMessage($level, $message),
            'attachments' => $this->buildAttachments($level, $message, $context),
        ];

        if ($this->iconEmoji) {
            $payload['icon_emoji'] = $this->iconEmoji;
        }

        $this->sendToSlack($payload);
    }

    protected function formatSlackMessage(LogLevel $level, string $message): string
    {
        $emoji = $this->getEmojiForLevel($level);
        return "{$emoji} *[{$level->value}]* {$message}";
    }

    protected function buildAttachments(LogLevel $level, string $message, array $context): array
    {
        if (empty($context)) {
            return [];
        }

        return [
            [
                'color' => $this->getColorForLevel($level),
                'fields' => [
                    [
                        'title' => 'Context',
                        'value' => '```' . json_encode($context, JSON_PRETTY_PRINT) . '```',
                        'short' => false,
                    ],
                    [
                        'title' => 'Time',
                        'value' => date('Y-m-d H:i:s'),
                        'short' => true,
                    ],
                ],
            ],
        ];
    }

    protected function sendToSlack(array $payload): void
    {
        $ch = curl_init($this->webhookUrl);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
        ]);

        curl_exec($ch);
        curl_close($ch);
    }

    protected function getColorForLevel(LogLevel $level): string
    {
        return match ($level) {
            LogLevel::EMERGENCY, LogLevel::ALERT, LogLevel::CRITICAL => 'danger',
            LogLevel::ERROR => 'danger',
            LogLevel::WARNING => 'warning',
            LogLevel::NOTICE, LogLevel::INFO => 'good',
            LogLevel::DEBUG => '#cccccc',
        };
    }

    protected function getEmojiForLevel(LogLevel $level): string
    {
        return match ($level) {
            LogLevel::EMERGENCY => ':rotating_light:',
            LogLevel::ALERT => ':exclamation:',
            LogLevel::CRITICAL => ':x:',
            LogLevel::ERROR => ':red_circle:',
            LogLevel::WARNING => ':warning:',
            LogLevel::NOTICE => ':information_source:',
            LogLevel::INFO => ':white_check_mark:',
            LogLevel::DEBUG => ':mag:',
        };
    }
}
