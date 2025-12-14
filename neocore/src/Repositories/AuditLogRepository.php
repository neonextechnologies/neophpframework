<?php

declare(strict_types=1);

namespace NeoCore\Repositories;

use Cycle\ORM\Select;
use Cycle\ORM\Select\Repository;
use NeoCore\Entities\AuditLog;

class AuditLogRepository extends Repository
{
    /**
     * Find logs by user
     */
    public function findByUser(int $userId): Select
    {
        return $this->select()->where('user_id', $userId);
    }

    /**
     * Find logs by event
     */
    public function findByEvent(string $event): Select
    {
        return $this->select()->where('event', $event);
    }

    /**
     * Find logs for auditable
     */
    public function findForAuditable(string $type, int $id): Select
    {
        return $this->select()
            ->where('auditable_type', $type)
            ->where('auditable_id', $id)
            ->orderBy('created_at', 'DESC');
    }

    /**
     * Find logs between dates
     */
    public function findBetweenDates(\DateTimeInterface $from, \DateTimeInterface $to): Select
    {
        return $this->select()
            ->where('created_at', '>=', $from)
            ->where('created_at', '<=', $to)
            ->orderBy('created_at', 'DESC');
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
     * Get user activity
     */
    public function getUserActivity(int $userId, int $limit = 50): Select
    {
        return $this->select()
            ->where('user_id', $userId)
            ->orderBy('created_at', 'DESC')
            ->limit($limit);
    }

    /**
     * Count by event
     */
    public function countByEvent(): array
    {
        $logs = $this->select()->fetchAll();

        $counts = [];
        foreach ($logs as $log) {
            $event = $log->event;
            $counts[$event] = ($counts[$event] ?? 0) + 1;
        }

        arsort($counts);

        return $counts;
    }

    /**
     * Clean old logs
     */
    public function cleanOldLogs(int $days = 90): int
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
}
