<?php

declare(strict_types=1);

namespace NeoCore\Storage\Commands;

use NeoCore\Console\Command;

class StorageLinkCommand extends Command
{
    protected string $signature = 'storage:link';
    protected string $description = 'Create symbolic links for public storage';

    public function handle(): int
    {
        $config = require base_path('config/filesystems.php');
        $links = $config['links'] ?? [];

        if (empty($links)) {
            $this->error('No symbolic links configured');
            return self::FAILURE;
        }

        foreach ($links as $target => $link) {
            if (file_exists($target)) {
                $this->info("The [{$target}] link already exists.");
                continue;
            }

            // Create target directory if it doesn't exist
            if (!is_dir($link)) {
                mkdir($link, 0755, true);
            }

            // Create symbolic link
            if (symlink($link, $target)) {
                $this->success("The [{$target}] link has been created.");
            } else {
                $this->error("Failed to create the [{$target}] link.");
            }
        }

        return self::SUCCESS;
    }
}
