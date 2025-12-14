<?php

declare(strict_types=1);

namespace NeoCore\Storage\Commands;

use NeoCore\Console\Command;

class StorageClearCommand extends Command
{
    protected string $signature = 'storage:clear {disk?}';
    protected string $description = 'Clear all files from storage disk';

    public function handle(): int
    {
        $disk = $this->argument('disk') ?? 'local';

        $this->warn("This will delete all files from the '{$disk}' disk!");
        
        if (!$this->confirm('Are you sure you want to continue?')) {
            $this->info('Operation cancelled.');
            return self::SUCCESS;
        }

        try {
            $storage = storage($disk);
            $files = $storage->files('', true);

            if (empty($files)) {
                $this->info("The '{$disk}' disk is already empty.");
                return self::SUCCESS;
            }

            $this->info("Deleting " . count($files) . " files...");

            $deleted = 0;
            foreach ($files as $file) {
                if ($storage->delete($file)) {
                    $deleted++;
                }
            }

            $this->success("Successfully deleted {$deleted} files from the '{$disk}' disk.");

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to clear storage: " . $e->getMessage());
            return self::FAILURE;
        }
    }
}
