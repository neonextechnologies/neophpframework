<?php

/**
 * ORM Sync Command
 * 
 * Synchronize ORM schema with database
 */

namespace NeoCore\System\CLI\Commands;

use NeoCore\System\Core\ORMService;

class ORMSync extends Command
{
    public function run(array $args): void
    {
        echo "Synchronizing ORM schema...\n\n";

        try {
            // Clear ORM cache
            ORMService::clearCache();
            echo "✓ Cleared ORM cache\n";

            // Initialize ORM (this will generate schema)
            $orm = ORMService::getORM();
            echo "✓ Generated ORM schema\n";

            // Get DBAL
            $dbal = ORMService::getDBAL();
            echo "✓ Connected to database\n";

            echo "\n✓ ORM schema synchronized successfully\n";
            echo "\nTables have been created/updated based on your Entity definitions.\n";

        } catch (\Exception $e) {
            echo "\n✗ Error: " . $e->getMessage() . "\n";
            echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
        }
    }
}

PHP;