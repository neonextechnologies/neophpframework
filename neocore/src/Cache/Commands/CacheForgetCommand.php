<?php

declare(strict_types=1);

namespace NeoCore\Cache\Commands;

use NeoCore\Console\Command;

class CacheForgetCommand extends Command
{
    protected string $signature = 'cache:forget {key} {store?}';
    protected string $description = 'Remove an item from the cache';

    public function handle(): int
    {
        $key = $this->argument('key');
        $store = $this->argument('store');

        try {
            if ($store) {
                $result = cache()->store($store)->forget($key);
            } else {
                $result = cache()->forget($key);
            }

            if ($result) {
                $this->success("Cache key '{$key}' removed successfully");
            } else {
                $this->warn("Cache key '{$key}' not found");
            }

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to remove cache key: " . $e->getMessage());
            return self::FAILURE;
        }
    }
}
