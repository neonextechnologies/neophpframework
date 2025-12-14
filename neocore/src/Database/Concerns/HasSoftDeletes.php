<?php

declare(strict_types=1);

namespace NeoCore\Database\Concerns;

use DateTimeImmutable;
use NeoCore\Database\SoftDeleteQueryBuilder;

/**
 * Has Soft Deletes
 * 
 * Trait for repositories to handle soft delete queries
 */
trait HasSoftDeletes
{
    /**
     * Get query builder with soft delete support.
     */
    public function query(): SoftDeleteQueryBuilder
    {
        return new SoftDeleteQueryBuilder($this->orm, $this->role);
    }

    /**
     * Find all records including soft deleted.
     */
    public function findWithTrashed(): array
    {
        return $this->query()->withTrashed()->fetchAll();
    }

    /**
     * Find only soft deleted records.
     */
    public function findOnlyTrashed(): array
    {
        return $this->query()->onlyTrashed()->fetchAll();
    }

    /**
     * Find a record by ID including soft deleted.
     */
    public function findByIdWithTrashed(int|string $id): ?object
    {
        return $this->query()
            ->withTrashed()
            ->where('id', $id)
            ->fetchOne();
    }

    /**
     * Restore a soft deleted record by ID.
     */
    public function restoreById(int|string $id): bool
    {
        $entity = $this->findByIdWithTrashed($id);

        if ($entity && method_exists($entity, 'trashed') && $entity->trashed()) {
            $entity->restore();
            return true;
        }

        return false;
    }

    /**
     * Restore all soft deleted records matching criteria.
     */
    public function restoreWhere(array $criteria): int
    {
        $query = $this->query()->onlyTrashed();

        foreach ($criteria as $column => $value) {
            $query->where($column, $value);
        }

        return $query->restore();
    }

    /**
     * Force delete a record by ID.
     */
    public function forceDeleteById(int|string $id): bool
    {
        $entity = $this->findByIdWithTrashed($id);

        if ($entity && method_exists($entity, 'forceDelete')) {
            $entity->forceDelete();
            return true;
        }

        return false;
    }

    /**
     * Force delete all records matching criteria.
     */
    public function forceDeleteWhere(array $criteria): int
    {
        $query = $this->query()->withTrashed();

        foreach ($criteria as $column => $value) {
            $query->where($column, $value);
        }

        return $query->forceDelete();
    }

    /**
     * Soft delete a record by ID.
     */
    public function softDeleteById(int|string $id): bool
    {
        $entity = $this->findById($id);

        if ($entity && method_exists($entity, 'delete')) {
            $entity->delete();
            return true;
        }

        return false;
    }

    /**
     * Soft delete all records matching criteria.
     */
    public function softDeleteWhere(array $criteria): int
    {
        $query = $this->query();
        $count = 0;

        foreach ($criteria as $column => $value) {
            $query->where($column, $value);
        }

        foreach ($query->fetchAll() as $entity) {
            if (method_exists($entity, 'delete')) {
                $entity->delete();
                $count++;
            }
        }

        return $count;
    }

    /**
     * Check if a record is soft deleted.
     */
    public function isTrashed(int|string $id): bool
    {
        $entity = $this->findByIdWithTrashed($id);

        if ($entity && method_exists($entity, 'trashed')) {
            return $entity->trashed();
        }

        return false;
    }

    /**
     * Get count of soft deleted records.
     */
    public function countTrashed(): int
    {
        return count($this->query()->onlyTrashed()->fetchAll());
    }

    /**
     * Clean up old soft deleted records.
     * 
     * @param int $days Number of days to keep soft deleted records
     */
    public function pruneDeleted(int $days = 30): int
    {
        $query = $this->query()->onlyTrashed();
        $cutoffDate = (new DateTimeImmutable())->modify("-{$days} days");
        $count = 0;

        foreach ($query->fetchAll() as $entity) {
            $deletedAtColumn = 'deleted_at';
            
            if (property_exists($entity, $deletedAtColumn)) {
                $deletedAt = $entity->{$deletedAtColumn};

                if ($deletedAt instanceof DateTimeImmutable && $deletedAt < $cutoffDate) {
                    if (method_exists($entity, 'forceDelete')) {
                        $entity->forceDelete();
                        $count++;
                    }
                }
            }
        }

        return $count;
    }
}
