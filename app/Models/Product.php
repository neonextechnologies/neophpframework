<?php

/**
 * Example Product Model
 */

namespace App\Models;

use NeoCore\System\Core\Model;
use PDO;

class Product extends Model
{
    protected string $table = 'products';
    protected string $primaryKey = 'id';

    /**
     * Get products by category
     */
    public function getByCategory(string $category, int $limit = 20): array
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE category = :category 
                AND status = 'active' 
                ORDER BY created_at DESC 
                LIMIT :limit";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':category', $category, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Search products
     */
    public function search(string $keyword): array
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE (name LIKE :keyword OR description LIKE :keyword) 
                AND status = 'active'
                ORDER BY name ASC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['keyword' => "%{$keyword}%"]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Update stock
     */
    public function updateStock(int $productId, int $quantity): bool
    {
        $sql = "UPDATE {$this->table} 
                SET stock = stock + :quantity, 
                    updated_at = :updated_at 
                WHERE id = :id";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'quantity' => $quantity,
            'updated_at' => date('Y-m-d H:i:s'),
            'id' => $productId
        ]);
    }
}
