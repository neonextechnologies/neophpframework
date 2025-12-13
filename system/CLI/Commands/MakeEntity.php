<?php

/**
 * Make Entity Command
 * 
 * Creates a new Cycle ORM Entity class
 */

namespace NeoCore\System\CLI\Commands;

class MakeEntity extends Command
{
    public function run(array $args): void
    {
        if (empty($args[0])) {
            echo "Usage: php neocore make:entity <EntityName>\n";
            echo "Example: php neocore make:entity Post\n";
            return;
        }

        $entityName = $args[0];
        $entityFile = BASE_PATH . "/app/Entities/{$entityName}.php";

        if (file_exists($entityFile)) {
            echo "✗ Entity already exists: {$entityFile}\n";
            return;
        }

        $template = <<<PHP
<?php

/**
 * {$entityName} Entity
 */

namespace App\Entities;

use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Table;

#[Entity]
#[Table(name: '{{table_name}}')]
class {$entityName}
{
    #[Column(type: 'primary')]
    public ?int \$id = null;

    #[Column(type: 'string', nullable: false)]
    public string \$name;

    #[Column(type: 'string', default: 'active')]
    public string \$status = 'active';

    #[Column(type: 'datetime')]
    public \DateTimeImmutable \$createdAt;

    #[Column(type: 'datetime', nullable: true)]
    public ?\DateTimeImmutable \$updatedAt = null;

    public function __construct()
    {
        \$this->createdAt = new \DateTimeImmutable();
    }
}

PHP;

        $tableName = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $entityName)) . 's';
        $template = str_replace('{{table_name}}', $tableName, $template);

        file_put_contents($entityFile, $template);

        echo "✓ Entity created: {$entityFile}\n";
        echo "\nNext steps:\n";
        echo "1. Customize your entity properties\n";
        echo "2. Run: php neocore make:repository {$entityName}\n";
        echo "3. Run: php neocore orm:sync to create database table\n";
    }
}

PHP;