<?php

/**
 * Base Repository Class
 * 
 * Extends Cycle ORM Repository with additional methods
 */

namespace App\Repositories;

use Cycle\ORM\Select;
use Cycle\ORM\Select\Repository as CycleRepository;

class Repository extends CycleRepository
{
    /**
     * Find one by criteria
     */
    public function findOneBy(array $criteria): ?object
    {
        $query = $this->select();
        
        foreach ($criteria as $field => $value) {
            $query->where($field, $value);
        }
        
        return $query->fetchOne();
    }

    /**
     * Find all by criteria
     */
    public function findBy(array $criteria, array $orderBy = [], ?int $limit = null, ?int $offset = null): array
    {
        $query = $this->select();
        
        foreach ($criteria as $field => $value) {
            $query->where($field, $value);
        }
        
        foreach ($orderBy as $field => $direction) {
            $query->orderBy($field, $direction);
        }
        
        if ($limit !== null) {
            $query->limit($limit);
        }
        
        if ($offset !== null) {
            $query->offset($offset);
        }
        
        return $query->fetchAll();
    }

    /**
     * Count by criteria
     */
    public function countBy(array $criteria = []): int
    {
        $query = $this->select();
        
        foreach ($criteria as $field => $value) {
            $query->where($field, $value);
        }
        
        return $query->count();
    }

    /**
     * Paginate results
     */
    public function paginate(int $page = 1, int $perPage = 20, array $criteria = []): array
    {
        $offset = ($page - 1) * $perPage;
        
        $items = $this->findBy($criteria, [], $perPage, $offset);
        $total = $this->countBy($criteria);
        
        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => (int) ceil($total / $perPage),
        ];
    }

    /**
     * Find all with limit
     */
    public function findAll(int $limit = 100): array
    {
        return $this->select()->limit($limit)->fetchAll();
    }

    /**
     * Check if entity exists by criteria
     */
    public function exists(array $criteria): bool
    {
        return $this->findOneBy($criteria) !== null;
    }
}
