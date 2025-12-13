<?php

/**
 * Clear View Cache Command
 * 
 * Clears Latte template cache
 */

namespace NeoCore\System\CLI\Commands;

use NeoCore\System\Core\ViewService;

class ClearViewCache extends Command
{
    public function run(array $args): void
    {
        echo "Clearing view cache...\n";

        try {
            ViewService::clearCache();
            echo "✓ View cache cleared successfully\n";
        } catch (\Exception $e) {
            echo "✗ Error: " . $e->getMessage() . "\n";
        }
    }
}

PHP;