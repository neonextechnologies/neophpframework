<?php

/**
 * Example Migration - Create Users Table
 * 
 * Run: php neocore migrate
 * Rollback: php neocore migrate:rollback
 */

namespace Database\Migrations;

use NeoCore\System\Core\Migration;
use PDO;

class CreateUsersTable extends Migration
{
    public function up(): void
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                uuid VARCHAR(36) UNIQUE,
                name VARCHAR(100) NOT NULL,
                email VARCHAR(255) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                status ENUM('active', 'inactive') DEFAULT 'active',
                last_login DATETIME NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NULL,
                INDEX idx_email (email),
                INDEX idx_status (status),
                INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";

        $this->pdo->exec($sql);
        echo "✓ Created users table\n";
    }

    public function down(): void
    {
        $sql = "DROP TABLE IF EXISTS users";
        $this->pdo->exec($sql);
        echo "✓ Dropped users table\n";
    }
}
