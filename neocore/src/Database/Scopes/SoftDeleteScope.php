<?php

declare(strict_types=1);

namespace NeoCore\Database\Scopes;

use Cycle\ORM\Select;

/**
 * Soft Delete Scope
 * 
 * Automatically filters soft-deleted records from queries
 */
class SoftDeleteScope
{
    protected string $deletedAtColumn;

    public function __construct(string $deletedAtColumn = 'deleted_at')
    {
        $this->deletedAtColumn = $deletedAtColumn;
    }

    /**
     * Apply the scope to the query.
     */
    public function apply(Select $query): void
    {
        $query->where($this->deletedAtColumn, '=', null);
    }

    /**
     * Remove the scope from the query.
     */
    public function remove(Select $query): void
    {
        // Remove the soft delete constraint
        // This depends on how Cycle ORM handles where clauses
        // You might need to rebuild the query without the soft delete constraint
    }

    /**
     * Extend the query with soft delete methods.
     */
    public function extend(Select $query): void
    {
        // Add withTrashed, onlyTrashed, etc. methods to the query
        // This can be done through query builder extension
    }
}
