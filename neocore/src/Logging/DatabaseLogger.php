<?php

declare(strict_types=1);

namespace NeoCore\Logging;

use Cycle\ORM\EntityManagerInterface;
use NeoCore\Entities\Log;

/**
 * Database Logger
 * 
 * Stores logs in database
 */
class DatabaseLogger extends AbstractLogger
{
    protected EntityManagerInterface $entityManager;
    protected string $channel;

    public function __construct(
        EntityManagerInterface $entityManager,
        string $channel = 'database',
        LogLevel $level = LogLevel::DEBUG
    ) {
        parent::__construct($level);
        $this->entityManager = $entityManager;
        $this->channel = $channel;
    }

    protected function write(LogLevel $level, string $message, array $context = []): void
    {
        $log = new Log();
        $log->level = $level->value;
        $log->channel = $this->channel;
        $log->message = $message;
        $log->context = !empty($context) ? $context : null;

        $this->entityManager->persist($log);
        $this->entityManager->run();
    }
}
