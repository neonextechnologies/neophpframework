<?php

declare(strict_types=1);

namespace NeoCore\Database\Traits;

use DateTimeImmutable;

/**
 * Soft Deletes Trait
 * 
 * Adds soft delete functionality to Cycle ORM entities
 */
trait SoftDeletes
{
    /**
     * The name of the "deleted at" column.
     */
    protected string $deletedAtColumn = 'deleted_at';

    /**
     * Indicates if the model is currently force deleting.
     */
    protected bool $forceDeleting = false;

    /**
     * Boot the soft deleting trait for a model.
     */
    public static function bootSoftDeletes(): void
    {
        // This method can be called by the ORM bootstrapper
        // to set up any global scopes or event listeners
    }

    /**
     * Mark the entity as soft deleted.
     */
    public function delete(): void
    {
        if ($this->forceDeleting) {
            $this->forceDelete();
            return;
        }

        $this->runSoftDelete();
    }

    /**
     * Perform the soft delete operation.
     */
    protected function runSoftDelete(): void
    {
        $column = $this->getDeletedAtColumn();
        $this->{$column} = new DateTimeImmutable();

        // If using Cycle ORM, you need to persist the change
        // This assumes you have access to the entity manager
        if (method_exists($this, 'save')) {
            $this->save();
        }
    }

    /**
     * Force delete the entity (permanent delete).
     */
    public function forceDelete(): void
    {
        $this->forceDeleting = true;

        // Perform actual deletion
        // This depends on your ORM implementation
        if (method_exists($this, 'performDelete')) {
            $this->performDelete();
        }

        $this->forceDeleting = false;
    }

    /**
     * Restore a soft-deleted entity.
     */
    public function restore(): void
    {
        $column = $this->getDeletedAtColumn();
        $this->{$column} = null;

        if (method_exists($this, 'save')) {
            $this->save();
        }
    }

    /**
     * Determine if the entity has been soft-deleted.
     */
    public function trashed(): bool
    {
        $column = $this->getDeletedAtColumn();
        return $this->{$column} !== null;
    }

    /**
     * Get the name of the "deleted at" column.
     */
    public function getDeletedAtColumn(): string
    {
        return $this->deletedAtColumn;
    }

    /**
     * Get the fully qualified "deleted at" column.
     */
    public function getQualifiedDeletedAtColumn(): string
    {
        return $this->getTable() . '.' . $this->getDeletedAtColumn();
    }

    /**
     * Determine if the entity is currently force deleting.
     */
    public function isForceDeleting(): bool
    {
        return $this->forceDeleting;
    }

    /**
     * Get the table name (should be implemented by entity).
     */
    protected function getTable(): string
    {
        // This should be implemented by the entity
        // or retrieved from the ORM schema
        return '';
    }
}
