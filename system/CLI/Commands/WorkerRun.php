<?php

namespace NeoCore\System\CLI\Commands;

use NeoCore\System\Core\Config;
use NeoCore\System\Core\Queue;
use NeoCore\System\Core\Worker;

/**
 * WorkerRun - Run queue worker
 */
class WorkerRun extends Command
{
    public function execute(array $args): int
    {
        $queueName = $this->argument($args, 0, 'default');

        $this->info("Starting worker for queue: $queueName");

        Config::init($this->basePath . '/config');
        $queueConfig = Config::get('queue');

        $driver = $queueConfig['driver'] ?? 'file';
        $config = $queueConfig[$driver] ?? [];

        try {
            $queue = new Queue($driver, $config);
            $worker = new Worker($queue, 3, 3);

            // Handle graceful shutdown
            pcntl_signal(SIGTERM, function() use ($worker) {
                $worker->stop();
            });

            pcntl_signal(SIGINT, function() use ($worker) {
                $worker->stop();
            });

            $worker->run($queueName);

            return 0;

        } catch (\Exception $e) {
            $this->error("Worker error: " . $e->getMessage());
            return 1;
        }
    }
}
