<?php

namespace NeoCore\System\CLI\Commands;

/**
 * MakeModel - Create new model
 */
class MakeModel extends Command
{
    public function execute(array $args): int
    {
        $modelName = $this->argument($args, 0);

        if (!$modelName) {
            $this->error("Model name is required");
            $this->info("Usage: php neocore make:model <name>");
            return 1;
        }

        $modelName = ucfirst($modelName);
        $modelPath = $this->basePath . '/app/Models/' . $modelName . '.php';

        if (file_exists($modelPath)) {
            $this->error("Model already exists: $modelName");
            return 1;
        }

        $content = $this->getModelTemplate($modelName);

        if ($this->createFile($modelPath, $content)) {
            $this->success("Model created: $modelName");
            $this->info("Location: app/Models/$modelName.php");
            return 0;
        }

        return 1;
    }

    private function getModelTemplate(string $name): string
    {
        $tableName = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $name)) . 's';
        
        return <<<PHP
<?php

namespace App\\Models;

use NeoCore\\System\\Core\\Model;

class $name extends Model
{
    protected string \$table = '$tableName';
    protected string \$primaryKey = 'id';

    // No magic relationships
    // No auto-casting
    // Simple database operations only
    
    /**
     * Example: Get active records
     */
    public function getActive(): array
    {
        return \$this->findWhere(['status' => 'active']);
    }

    /**
     * Example: Custom query
     */
    public function findByEmail(string \$email): ?array
    {
        return \$this->findFirstWhere(['email' => \$email]);
    }
}

PHP;
    }
}
