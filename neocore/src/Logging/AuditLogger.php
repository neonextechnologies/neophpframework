<?php

declare(strict_types=1);

namespace NeoCore\Logging;

use Cycle\ORM\EntityManagerInterface;
use NeoCore\Entities\AuditLog;

/**
 * Audit Logger
 * 
 * Logs user actions and changes
 */
class AuditLogger
{
    protected EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Log an event
     */
    public function log(
        string $event,
        ?int $userId = null,
        ?string $auditableType = null,
        ?int $auditableId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?array $metadata = null
    ): AuditLog {
        $log = new AuditLog();
        $log->event = $event;
        $log->user_id = $userId;
        $log->auditable_type = $auditableType;
        $log->auditable_id = $auditableId;
        $log->old_values = $oldValues;
        $log->new_values = $newValues;
        $log->ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
        $log->user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $log->metadata = $metadata;

        $this->entityManager->persist($log);
        $this->entityManager->run();

        return $log;
    }

    /**
     * Log a created event
     */
    public function created(
        string $type,
        int $id,
        ?int $userId = null,
        ?array $values = null
    ): AuditLog {
        return $this->log(
            event: 'created',
            userId: $userId,
            auditableType: $type,
            auditableId: $id,
            newValues: $values
        );
    }

    /**
     * Log an updated event
     */
    public function updated(
        string $type,
        int $id,
        ?int $userId = null,
        ?array $oldValues = null,
        ?array $newValues = null
    ): AuditLog {
        return $this->log(
            event: 'updated',
            userId: $userId,
            auditableType: $type,
            auditableId: $id,
            oldValues: $oldValues,
            newValues: $newValues
        );
    }

    /**
     * Log a deleted event
     */
    public function deleted(
        string $type,
        int $id,
        ?int $userId = null,
        ?array $values = null
    ): AuditLog {
        return $this->log(
            event: 'deleted',
            userId: $userId,
            auditableType: $type,
            auditableId: $id,
            oldValues: $values
        );
    }

    /**
     * Log a login event
     */
    public function login(int $userId, ?array $metadata = null): AuditLog
    {
        return $this->log(
            event: 'login',
            userId: $userId,
            metadata: $metadata
        );
    }

    /**
     * Log a logout event
     */
    public function logout(int $userId): AuditLog
    {
        return $this->log(
            event: 'logout',
            userId: $userId
        );
    }

    /**
     * Log a failed login event
     */
    public function loginFailed(string $username): AuditLog
    {
        return $this->log(
            event: 'login_failed',
            metadata: ['username' => $username]
        );
    }

    /**
     * Log a password change event
     */
    public function passwordChanged(int $userId): AuditLog
    {
        return $this->log(
            event: 'password_changed',
            userId: $userId
        );
    }

    /**
     * Log a custom event
     */
    public function custom(
        string $event,
        ?int $userId = null,
        ?array $metadata = null
    ): AuditLog {
        return $this->log(
            event: $event,
            userId: $userId,
            metadata: $metadata
        );
    }

    /**
     * Log with current user
     */
    public function logWithCurrentUser(
        string $event,
        ?string $auditableType = null,
        ?int $auditableId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?array $metadata = null
    ): AuditLog {
        $userId = $this->getCurrentUserId();

        return $this->log(
            event: $event,
            userId: $userId,
            auditableType: $auditableType,
            auditableId: $auditableId,
            oldValues: $oldValues,
            newValues: $newValues,
            metadata: $metadata
        );
    }

    /**
     * Get current user ID
     */
    protected function getCurrentUserId(): ?int
    {
        // This should integrate with your authentication system
        // For now, return null
        return null;
    }
}
