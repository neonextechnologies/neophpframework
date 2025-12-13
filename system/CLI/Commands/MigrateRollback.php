<?php

namespace NeoCore\System\CLI\Commands;

use NeoCore\System\Core\Config;
use NeoCore\System\Core\Migration;
use PDO;

/**
 * MigrateRollback - Rollback last migration batch
 */
class MigrateRollback extends Command
{
    public function execute(array $args): int
    {
        $this->info("Rolling back migrations...");

        Config::init($this->basePath . '/config');
        $dbConfig = Config::get('database.default');

        if (!$dbConfig) {
            $this->error("Database configuration not found");
            return 1;
        }

        try {
            $db = $this->createConnection($dbConfig);

            $lastBatch = Migration::getLastBatch($db);

            if ($lastBatch === 0) {
                $this->info("Nothing to rollback");
                return 0;
            }

            $migrations = Migration::getMigrationsByBatch($db, $lastBatch);

            if (empty($migrations)) {
                $this->info("Nothing to rollback");
                return 0;
            }

            $migrationFiles = $this->getMigrationFiles();

            foreach ($migrations as $migrationName) {
                $this->info("Rolling back: $migrationName");

                $file = $this->findMigrationFile($migrationFiles, $migrationName);

                if (!$file) {
                    $this->error("Migration file not found: $migrationName");
                    continue;
                }

                require_once $file;
                
                $className = $this->getClassNameFromFile($file);

                if (!class_exists($className)) {
                    $this->error("Migration class not found: $className");
                    continue;
                }

                $migration = new $className($db);
                $migration->down();

                Migration::removeMigration($db, $migrationName);

                $this->success("Rolled back: $migrationName");
            }

            return 0;

        } catch (\Exception $e) {
            $this->error("Rollback failed: " . $e->getMessage());
            return 1;
        }
    }

    private function createConnection(array $config): PDO
    {
        $driver = $config['driver'] ?? 'mysql';
        $host = $config['host'] ?? 'localhost';
        $port = $config['port'] ?? 3306;
        $database = $config['database'];
        $username = $config['username'];
        $password = $config['password'];
        $charset = $config['charset'] ?? 'utf8mb4';

        $dsn = "$driver:host=$host;port=$port;dbname=$database;charset=$charset";

        return new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
    }

    private function getMigrationFiles(): array
    {
        $files = [];

        $appMigrationsPath = $this->basePath . '/storage/migrations';
        if (is_dir($appMigrationsPath)) {
            $files = array_merge($files, glob($appMigrationsPath . '/*.php'));
        }

        $modulesPath = $this->basePath . '/modules';
        if (is_dir($modulesPath)) {
            $modules = glob($modulesPath . '/*', GLOB_ONLYDIR);
            foreach ($modules as $module) {
                $migrationsPath = $module . '/Migrations';
                if (is_dir($migrationsPath)) {
                    $files = array_merge($files, glob($migrationsPath . '/*.php'));
                }
            }
        }

        return $files;
    }

    private function findMigrationFile(array $files, string $migrationName): ?string
    {
        foreach ($files as $file) {
            if (basename($file, '.php') === $migrationName) {
                return $file;
            }
        }
        return null;
    }

    private function getClassNameFromFile(string $file): string
    {
        $content = file_get_contents($file);
        
        if (preg_match('/class\s+(\w+)\s+extends/', $content, $matches)) {
            return $matches[1];
        }

        $basename = basename($file, '.php');
        $parts = explode('_', $basename);
        array_shift($parts);
        array_shift($parts);
        array_shift($parts);
        
        return implode('', array_map('ucfirst', $parts));
    }
}
