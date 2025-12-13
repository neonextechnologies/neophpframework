<?php

/**
 * Product Repository
 */

namespace App\Repositories;

class ProductRepository extends Repository
{
    /**
     * Find products by category
     */
    public function findByCategory(string $category, int $limit = 20): array
    {
        return $this->findBy(
            ['category' => $category, 'status' => 'active'],
            ['createdAt' => 'DESC'],
            $limit
        );
    }

    /**
     * Find products by slug
     */
    public function findBySlug(string $slug): ?object
    {
        return $this->findOneBy(['slug' => $slug]);
    }

    /**
     * Find in-stock products
     */
    public function findInStock(int $limit = 100): array
    {
        return $this->select()
            ->where('stock', '>', 0)
            ->where('status', 'active')
            ->limit($limit)
            ->fetchAll();
    }

    /**
     * Search products
     */
    public function search(string $keyword): array
    {
        return $this->select()
            ->where('name', 'LIKE', "%{$keyword}%")
            ->orWhere('description', 'LIKE', "%{$keyword}%")
            ->where('status', 'active')
            ->fetchAll();
    }

    /**
     * Find featured products
     */
    public function findFeatured(int $limit = 10): array
    {
        return $this->findBy(
            ['status' => 'active'],
            ['createdAt' => 'DESC'],
            $limit
        );
    }
}
