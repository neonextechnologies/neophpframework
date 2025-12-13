<?php

namespace NeoCore\System\CLI\Commands;

/**
 * MakeMigration - Create new migration
 */
class MakeMigration extends Command
{
    public function execute(array $args): int
    {
        $migrationName = $this->argument($args, 0);

        if (!$migrationName) {
            $this->error("Migration name is required");
            $this->info("Usage: php neocore make:migration <name>");
            return 1;
        }

        $timestamp = date('Y_m_d_His');
        $className = $this->generateClassName($migrationName);
        $fileName = $timestamp . '_' . $migrationName . '.php';

        // Check if module flag is provided
        $module = $this->argument($args, 1);

        if ($module && strpos($module, '--module=') === 0) {
            $moduleName = substr($module, 9);
            $migrationPath = $this->basePath . '/modules/' . strtolower($moduleName) . '/Migrations/' . $fileName;
        } else {
            // Create in app migrations directory
            $migrationPath = $this->basePath . '/storage/migrations/' . $fileName;
        }

        $content = $this->getMigrationTemplate($className, $migrationName);

        if ($this->createFile($migrationPath, $content)) {
            $this->success("Migration created: $fileName");
            $this->info("Location: $migrationPath");
            return 0;
        }

        return 1;
    }

    private function generateClassName(string $name): string
    {
        $parts = explode('_', $name);
        $className = implode('', array_map('ucfirst', $parts));
        return $className;
    }

    private function getMigrationTemplate(string $className, string $name): string
    {
        $tableName = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $className));
        
        return <<<PHP
<?php

use NeoCore\\System\\Core\\Migration;

class $className extends Migration
{
    /**
     * Run the migration
     */
    public function up(): void
    {
        \$sql = "CREATE TABLE $tableName (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";

        \$this->execute(\$sql);
    }

    /**
     * Rollback the migration
     */
    public function down(): void
    {
        \$sql = "DROP TABLE IF EXISTS $tableName";
        \$this->execute(\$sql);
    }
}

PHP;
    }
}
