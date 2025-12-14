<?php

declare(strict_types=1);

namespace NeoCore\Cache\Commands;

use NeoCore\Console\Command;
use NeoCore\Cache\FileCache;
use NeoCore\Cache\RedisCache;

class CacheStatsCommand extends Command
{
    protected string $signature = 'cache:stats {store?}';
    protected string $description = 'Display cache statistics';

    public function handle(): int
    {
        $store = $this->argument('store');

        try {
            $cache = $store ? cache()->store($store) : cache()->store();
            $storeName = $store ?? 'default';

            $this->info("Cache Statistics for store: {$storeName}");
            $this->line('');

            if ($cache instanceof RedisCache) {
                $this->displayRedisStats($cache);
            } elseif ($cache instanceof FileCache) {
                $this->displayFileStats($cache);
            } else {
                $this->warn('Statistics not available for this cache driver');
            }

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to get cache stats: " . $e->getMessage());
            return self::FAILURE;
        }
    }

    protected function displayRedisStats(RedisCache $cache): void
    {
        $stats = $cache->getStats();

        $this->table(
            ['Metric', 'Value'],
            [
                ['Cache Hits', number_format($stats['hits'])],
                ['Cache Misses', number_format($stats['misses'])],
                ['Total Keys', number_format($stats['keys'])],
                ['Memory Used', $stats['memory']],
                ['Hit Rate', $this->calculateHitRate($stats['hits'], $stats['misses'])],
            ]
        );
    }

    protected function displayFileStats(FileCache $cache): void
    {
        $this->info('File cache statistics:');
        $this->line('Prefix: ' . $cache->getPrefix());
        
        // Cleanup expired entries
        $deleted = $cache->cleanup();
        
        if ($deleted > 0) {
            $this->success("Cleaned up {$deleted} expired cache entries");
        } else {
            $this->info('No expired cache entries found');
        }
    }

    protected function calculateHitRate(int $hits, int $misses): string
    {
        $total = $hits + $misses;
        
        if ($total === 0) {
            return 'N/A';
        }

        $rate = ($hits / $total) * 100;
        return number_format($rate, 2) . '%';
    }
}
