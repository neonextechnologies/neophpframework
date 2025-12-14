<?php

declare(strict_types=1);

namespace NeoCore\Repositories;

use Cycle\ORM\Select;
use Cycle\ORM\Select\Repository;
use NeoCore\Entities\Log;

class LogRepository extends Repository
{
    /**
     * Find logs by level
     */
    public function findByLevel(string $level): Select
    {
        return $this->select()->where('level', $level);
    }

    /**
     * Find logs by channel
     */
    public function findByChannel(string $channel): Select
    {
        return $this->select()->where('channel', $channel);
    }

    /**
     * Find logs between dates
     */
    public function findBetweenDates(\DateTimeInterface $from, \DateTimeInterface $to): Select
    {
        return $this->select()
            ->where('created_at', '>=', $from)
            ->where('created_at', '<=', $to);
    }

    /**
     * Find recent logs
     */
    public function findRecent(int $limit = 100): Select
    {
        return $this->select()
            ->orderBy('created_at', 'DESC')
            ->limit($limit);
    }

    /**
     * Clean old logs
     */
    public function cleanOldLogs(int $days = 30): int
    {
        $date = new \DateTimeImmutable("-{$days} days");

        $logs = $this->select()
            ->where('created_at', '<', $date)
            ->fetchAll();

        $count = count($logs);

        foreach ($logs as $log) {
            $this->getEntityManager()->delete($log);
        }

        $this->getEntityManager()->run();

        return $count;
    }

    /**
     * Count logs by level
     */
    public function countByLevel(): array
    {
        $logs = $this->select()->fetchAll();

        $counts = [];
        foreach ($logs as $log) {
            $level = $log->level;
            $counts[$level] = ($counts[$level] ?? 0) + 1;
        }

        return $counts;
    }

    /**
     * Get error logs
     */
    public function getErrors(): Select
    {
        return $this->select()
            ->where('level', 'in', ['emergency', 'alert', 'critical', 'error'])
            ->orderBy('created_at', 'DESC');
    }
}
