<?php

namespace NeoCore\System\CLI\Commands;

use NeoCore\System\Core\Config;
use NeoCore\System\Core\Migration;
use PDO;

/**
 * Migrate - Run database migrations
 */
class Migrate extends Command
{
    public function execute(array $args): int
    {
        $this->info("Running migrations...");

        // Load database config
        Config::init($this->basePath . '/config');
        $dbConfig = Config::get('database.default');

        if (!$dbConfig) {
            $this->error("Database configuration not found");
            return 1;
        }

        try {
            $db = $this->createConnection($dbConfig);
            
            // Create migrations table
            Migration::createMigrationsTable($db);

            // Get migration files
            $migrations = $this->getMigrationFiles();

            if (empty($migrations)) {
                $this->info("No migrations to run");
                return 0;
            }

            $batch = Migration::getLastBatch($db) + 1;
            $ranCount = 0;

            foreach ($migrations as $file) {
                $migrationName = basename($file, '.php');

                if (Migration::hasRun($db, $migrationName)) {
                    continue;
                }

                $this->info("Migrating: $migrationName");

                require_once $file;
                
                // Extract class name from file
                $className = $this->getClassNameFromFile($file);

                if (!class_exists($className)) {
                    $this->error("Migration class not found: $className");
                    continue;
                }

                $migration = new $className($db);
                $migration->up();

                Migration::recordMigration($db, $migrationName, $batch);

                $this->success("Migrated: $migrationName");
                $ranCount++;
            }

            if ($ranCount === 0) {
                $this->info("Nothing to migrate");
            } else {
                $this->success("Ran $ranCount migration(s)");
            }

            return 0;

        } catch (\Exception $e) {
            $this->error("Migration failed: " . $e->getMessage());
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
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }

    private function getMigrationFiles(): array
    {
        $files = [];

        // Get app migrations
        $appMigrationsPath = $this->basePath . '/storage/migrations';
        if (is_dir($appMigrationsPath)) {
            $files = array_merge($files, glob($appMigrationsPath . '/*.php'));
        }

        // Get module migrations
        $modulesPath = $this->basePath . '/modules';
        if (is_dir($modulesPath)) {
            $modules = glob($modulesPath . '/*', GLOB_ONLYDIR);
            foreach ($modules as $module) {
                $migrationsPath = $module . '/Migrations';
                if (is_dir($migrationsPath)) {
                    $moduleFiles = glob($migrationsPath . '/*.php');
                    $files = array_merge($files, $moduleFiles);
                }
            }
        }

        sort($files);
        return $files;
    }

    private function getClassNameFromFile(string $file): string
    {
        $content = file_get_contents($file);
        
        if (preg_match('/class\s+(\w+)\s+extends/', $content, $matches)) {
            return $matches[1];
        }

        // Fallback: generate from filename
        $basename = basename($file, '.php');
        $parts = explode('_', $basename);
        array_shift($parts); // Remove timestamp parts
        array_shift($parts);
        array_shift($parts);
        
        return implode('', array_map('ucfirst', $parts));
    }
}
