<?php

namespace NeoCore\System\Core;

/**
 * Migration - Database migration base class
 * 
 * Simple up/down migration pattern.
 */
abstract class Migration
{
    protected \PDO $db;

    public function __construct(\PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Run migration
     */
    abstract public function up(): void;

    /**
     * Rollback migration
     */
    abstract public function down(): void;

    /**
     * Execute SQL query
     */
    protected function execute(string $sql): bool
    {
        return $this->db->exec($sql) !== false;
    }

    /**
     * Create migrations table
     */
    public static function createMigrationsTable(\PDO $db): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS migrations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            migration VARCHAR(255) NOT NULL,
            batch INT NOT NULL,
            executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_migration (migration)
        )";

        $db->exec($sql);
    }

    /**
     * Check if migration has been run
     */
    public static function hasRun(\PDO $db, string $migration): bool
    {
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM migrations WHERE migration = :migration");
        $stmt->execute(['migration' => $migration]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        return $result['count'] > 0;
    }

    /**
     * Record migration as run
     */
    public static function recordMigration(\PDO $db, string $migration, int $batch): void
    {
        $stmt = $db->prepare("INSERT INTO migrations (migration, batch) VALUES (:migration, :batch)");
        $stmt->execute([
            'migration' => $migration,
            'batch' => $batch
        ]);
    }

    /**
     * Remove migration record
     */
    public static function removeMigration(\PDO $db, string $migration): void
    {
        $stmt = $db->prepare("DELETE FROM migrations WHERE migration = :migration");
        $stmt->execute(['migration' => $migration]);
    }

    /**
     * Get last batch number
     */
    public static function getLastBatch(\PDO $db): int
    {
        $stmt = $db->query("SELECT MAX(batch) as batch FROM migrations");
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        return (int)($result['batch'] ?? 0);
    }

    /**
     * Get migrations by batch
     */
    public static function getMigrationsByBatch(\PDO $db, int $batch): array
    {
        $stmt = $db->prepare("SELECT migration FROM migrations WHERE batch = :batch ORDER BY id DESC");
        $stmt->execute(['batch' => $batch]);
        
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }
}
