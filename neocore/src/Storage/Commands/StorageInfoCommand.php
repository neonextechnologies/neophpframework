<?php

declare(strict_types=1);

namespace NeoCore\Storage\Commands;

use NeoCore\Console\Command;

class StorageInfoCommand extends Command
{
    protected string $signature = 'storage:info {disk?}';
    protected string $description = 'Display storage disk information';

    public function handle(): int
    {
        $disk = $this->argument('disk') ?? 'local';

        try {
            $storage = storage($disk);
            
            $this->info("Storage Disk: {$disk}");
            $this->line('');

            // Count files
            $files = $storage->files('', true);
            $fileCount = count($files);

            // Calculate total size
            $totalSize = 0;
            foreach ($files as $file) {
                $totalSize += $storage->size($file);
            }

            // Count directories
            $directories = $storage->directories('');
            $dirCount = count($directories);

            // Display statistics
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Total Files', $fileCount],
                    ['Total Directories', $dirCount],
                    ['Total Size', format_file_size($totalSize)],
                ]
            );

            // Display recent files
            if ($fileCount > 0) {
                $this->line('');
                $this->info('Recent Files:');
                
                $recentFiles = array_slice($files, 0, 10);
                $fileData = [];

                foreach ($recentFiles as $file) {
                    $fileData[] = [
                        'Path' => $file,
                        'Size' => format_file_size($storage->size($file)),
                        'Modified' => date('Y-m-d H:i:s', $storage->lastModified($file)),
                    ];
                }

                $this->table(
                    ['Path', 'Size', 'Modified'],
                    $fileData
                );

                if ($fileCount > 10) {
                    $this->line("... and " . ($fileCount - 10) . " more files");
                }
            }

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to get storage info: " . $e->getMessage());
            return self::FAILURE;
        }
    }
}
