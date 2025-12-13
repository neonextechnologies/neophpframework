<?php

/**
 * Make Repository Command
 * 
 * Creates a new Repository class
 */

namespace NeoCore\System\CLI\Commands;

class MakeRepository extends Command
{
    public function run(array $args): void
    {
        if (empty($args[0])) {
            echo "Usage: php neocore make:repository <EntityName>\n";
            echo "Example: php neocore make:repository Post\n";
            return;
        }

        $entityName = $args[0];
        $repositoryName = $entityName . 'Repository';
        $repositoryFile = BASE_PATH . "/app/Repositories/{$repositoryName}.php";

        if (file_exists($repositoryFile)) {
            echo "✗ Repository already exists: {$repositoryFile}\n";
            return;
        }

        $template = <<<PHP
<?php

/**
 * {$entityName} Repository
 */

namespace App\Repositories;

class {$repositoryName} extends Repository
{
    /**
     * Find {$entityName} by custom criteria
     */
    public function findByName(string \$name): ?object
    {
        return \$this->findOneBy(['name' => \$name]);
    }

    /**
     * Find active {$entityName}s
     */
    public function findActive(int \$limit = 100): array
    {
        return \$this->findBy(['status' => 'active'], ['createdAt' => 'DESC'], \$limit);
    }

    /**
     * Search {$entityName}s
     */
    public function search(string \$keyword): array
    {
        return \$this->select()
            ->where('name', 'LIKE', "%{\$keyword}%")
            ->fetchAll();
    }
}

PHP;

        file_put_contents($repositoryFile, $template);

        echo "✓ Repository created: {$repositoryFile}\n";
        echo "\nNext steps:\n";
        echo "1. Add custom query methods to your repository\n";
        echo "2. Update config/orm.php to map entity to repository\n";
    }
}

PHP;