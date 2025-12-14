<?php

declare(strict_types=1);

namespace NeoCore\Cache\Commands;

use NeoCore\Console\Command;

class CacheClearCommand extends Command
{
    protected string $signature = 'cache:clear {store?}';
    protected string $description = 'Clear the cache';

    public function handle(): int
    {
        $store = $this->argument('store');

        try {
            if ($store) {
                cache()->store($store)->flush();
                $this->success("Cache cleared for store: {$store}");
            } else {
                cache()->flush();
                $this->success('Cache cleared successfully');
            }

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to clear cache: " . $e->getMessage());
            return self::FAILURE;
        }
    }
}
