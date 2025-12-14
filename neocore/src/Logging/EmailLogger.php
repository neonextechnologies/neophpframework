<?php

declare(strict_types=1);

namespace NeoCore\Logging;

use NeoCore\Mail\MailerInterface;

/**
 * Email Logger
 * 
 * Sends logs to email
 */
class EmailLogger extends AbstractLogger
{
    protected MailerInterface $mailer;
    protected string $to;
    protected string $from;
    protected string $subject;

    public function __construct(
        MailerInterface $mailer,
        string $to,
        string $from,
        string $subject = 'Application Log',
        LogLevel $level = LogLevel::ERROR
    ) {
        parent::__construct($level);
        $this->mailer = $mailer;
        $this->to = $to;
        $this->from = $from;
        $this->subject = $subject;
    }

    protected function write(LogLevel $level, string $message, array $context = []): void
    {
        $body = $this->buildEmailBody($level, $message, $context);

        $this->mailer->send(
            to: $this->to,
            from: $this->from,
            subject: "[{$level->value}] {$this->subject}",
            body: $body,
            html: true
        );
    }

    protected function buildEmailBody(LogLevel $level, string $message, array $context): string
    {
        $html = '<html><body>';
        $html .= '<h2 style="color: ' . $this->getColorForLevel($level) . ';">';
        $html .= strtoupper($level->value);
        $html .= '</h2>';
        $html .= '<p><strong>Message:</strong> ' . htmlspecialchars($message) . '</p>';
        $html .= '<p><strong>Time:</strong> ' . date('Y-m-d H:i:s') . '</p>';

        if (!empty($context)) {
            $html .= '<h3>Context</h3>';
            $html .= '<pre style="background: #f5f5f5; padding: 10px; border-radius: 5px;">';
            $html .= htmlspecialchars(json_encode($context, JSON_PRETTY_PRINT));
            $html .= '</pre>';
        }

        if (isset($context['exception'])) {
            $exception = $context['exception'];
            if ($exception instanceof \Throwable) {
                $html .= '<h3>Exception</h3>';
                $html .= '<p><strong>Type:</strong> ' . get_class($exception) . '</p>';
                $html .= '<p><strong>Message:</strong> ' . htmlspecialchars($exception->getMessage()) . '</p>';
                $html .= '<p><strong>File:</strong> ' . $exception->getFile() . ':' . $exception->getLine() . '</p>';
                $html .= '<h4>Stack Trace</h4>';
                $html .= '<pre style="background: #f5f5f5; padding: 10px; border-radius: 5px;">';
                $html .= htmlspecialchars($exception->getTraceAsString());
                $html .= '</pre>';
            }
        }

        $html .= '</body></html>';

        return $html;
    }

    protected function getColorForLevel(LogLevel $level): string
    {
        return match ($level) {
            LogLevel::EMERGENCY, LogLevel::ALERT, LogLevel::CRITICAL => '#cc0000',
            LogLevel::ERROR => '#ff0000',
            LogLevel::WARNING => '#ff9900',
            LogLevel::NOTICE, LogLevel::INFO => '#0099cc',
            LogLevel::DEBUG => '#666666',
        };
    }
}
