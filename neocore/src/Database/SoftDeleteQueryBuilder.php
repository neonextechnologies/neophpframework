<?php

declare(strict_types=1);

namespace NeoCore\Database;

use Cycle\ORM\Select;
use DateTimeInterface;

/**
 * Soft Delete Query Builder
 * 
 * Extends Cycle ORM Select to add soft delete support
 */
class SoftDeleteQueryBuilder extends Select
{
    /**
     * Whether to include soft deleted records.
     */
    protected bool $withTrashed = false;

    /**
     * Whether to only include soft deleted records.
     */
    protected bool $onlyTrashed = false;

    /**
     * The column name for soft deletes.
     */
    protected string $deletedAtColumn = 'deleted_at';

    /**
     * Include soft deleted records in results.
     */
    public function withTrashed(): static
    {
        $this->withTrashed = true;
        $this->onlyTrashed = false;
        return $this;
    }

    /**
     * Only include soft deleted records in results.
     */
    public function onlyTrashed(): static
    {
        $this->onlyTrashed = true;
        $this->withTrashed = false;
        return $this;
    }

    /**
     * Only include non-deleted records (default behavior).
     */
    public function withoutTrashed(): static
    {
        $this->withTrashed = false;
        $this->onlyTrashed = false;
        return $this;
    }

    /**
     * Restore soft deleted records.
     */
    public function restore(): int
    {
        $count = 0;

        foreach ($this->fetchAll() as $entity) {
            if (method_exists($entity, 'restore')) {
                $entity->restore();
                $count++;
            }
        }

        return $count;
    }

    /**
     * Force delete records (permanent delete).
     */
    public function forceDelete(): int
    {
        $count = 0;

        foreach ($this->fetchAll() as $entity) {
            if (method_exists($entity, 'forceDelete')) {
                $entity->forceDelete();
                $count++;
            }
        }

        return $count;
    }

    /**
     * Override fetchAll to apply soft delete filters.
     */
    public function fetchAll(): array
    {
        $this->applySoftDeleteConstraints();
        return parent::fetchAll();
    }

    /**
     * Override fetchOne to apply soft delete filters.
     */
    public function fetchOne(): ?object
    {
        $this->applySoftDeleteConstraints();
        return parent::fetchOne();
    }

    /**
     * Apply soft delete constraints to the query.
     */
    protected function applySoftDeleteConstraints(): void
    {
        if ($this->withTrashed) {
            // Include all records (no filter)
            return;
        }

        if ($this->onlyTrashed) {
            // Only soft deleted records
            $this->where($this->deletedAtColumn, '!=', null);
        } else {
            // Only non-deleted records (default)
            $this->where($this->deletedAtColumn, '=', null);
        }
    }

    /**
     * Set the deleted at column name.
     */
    public function setDeletedAtColumn(string $column): static
    {
        $this->deletedAtColumn = $column;
        return $this;
    }

    /**
     * Get the deleted at column name.
     */
    public function getDeletedAtColumn(): string
    {
        return $this->deletedAtColumn;
    }
}
