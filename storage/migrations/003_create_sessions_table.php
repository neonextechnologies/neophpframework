<?php

/**
 * Example Migration - Create Sessions Table
 * 
 * For database-backed sessions
 */

namespace Database\Migrations;

use NeoCore\System\Core\Migration;
use PDO;

class CreateSessionsTable extends Migration
{
    public function up(): void
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS sessions (
                id VARCHAR(255) PRIMARY KEY,
                user_id INT NULL,
                ip_address VARCHAR(45),
                user_agent VARCHAR(255),
                payload TEXT NOT NULL,
                last_activity INT NOT NULL,
                INDEX idx_user_id (user_id),
                INDEX idx_last_activity (last_activity)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";

        $this->pdo->exec($sql);
        echo "✓ Created sessions table\n";
    }

    public function down(): void
    {
        $sql = "DROP TABLE IF EXISTS sessions";
        $this->pdo->exec($sql);
        echo "✓ Dropped sessions table\n";
    }
}
