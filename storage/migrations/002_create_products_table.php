<?php

/**
 * Example Migration - Create Products Table
 */

namespace Database\Migrations;

use NeoCore\System\Core\Migration;
use PDO;

class CreateProductsTable extends Migration
{
    public function up(): void
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS products (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                slug VARCHAR(255) UNIQUE NOT NULL,
                description TEXT,
                category VARCHAR(100),
                price DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
                stock INT NOT NULL DEFAULT 0,
                status ENUM('active', 'inactive') DEFAULT 'active',
                created_at DATETIME NOT NULL,
                updated_at DATETIME NULL,
                INDEX idx_category (category),
                INDEX idx_status (status),
                INDEX idx_slug (slug)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";

        $this->pdo->exec($sql);
        echo "✓ Created products table\n";
    }

    public function down(): void
    {
        $sql = "DROP TABLE IF EXISTS products";
        $this->pdo->exec($sql);
        echo "✓ Dropped products table\n";
    }
}
