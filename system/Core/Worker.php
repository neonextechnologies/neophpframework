<?php

namespace NeoCore\System\Core;

/**
 * Worker - Queue job processor
 * 
 * Processes jobs from queue with retry logic.
 */
class Worker
{
    private Queue $queue;
    private int $maxAttempts;
    private int $sleep;
    private bool $running = false;

    public function __construct(Queue $queue, int $maxAttempts = 3, int $sleep = 3)
    {
        $this->queue = $queue;
        $this->maxAttempts = $maxAttempts;
        $this->sleep = $sleep;
    }

    /**
     * Start worker
     */
    public function run(string $queueName): void
    {
        $this->running = true;

        echo "Worker started for queue: $queueName\n";

        while ($this->running) {
            $job = $this->queue->pop($queueName);

            if ($job === null) {
                // No jobs, sleep
                sleep($this->sleep);
                continue;
            }

            $this->processJob($job);
        }
    }

    /**
     * Process single job
     */
    public function processJob(array $job): void
    {
        echo "Processing job: {$job['id']}\n";

        try {
            $payload = $job['payload'];
            $handler = $payload['handler'] ?? null;

            if ($handler === null) {
                throw new \Exception('Job handler not specified');
            }

            if (!class_exists($handler)) {
                throw new \Exception("Job handler not found: $handler");
            }

            $instance = new $handler();
            
            if (!method_exists($instance, 'handle')) {
                throw new \Exception("Handler method 'handle' not found in $handler");
            }

            $data = $payload['data'] ?? [];
            $instance->handle($data);

            echo "Job completed: {$job['id']}\n";

        } catch (\Exception $e) {
            $this->handleJobFailure($job, $e);
        }
    }

    /**
     * Handle job failure
     */
    private function handleJobFailure(array $job, \Exception $e): void
    {
        $job['attempts']++;
        $job['last_error'] = $e->getMessage();
        $job['failed_at'] = time();

        echo "Job failed: {$job['id']} - {$e->getMessage()}\n";

        if ($job['attempts'] >= $this->maxAttempts) {
            echo "Job max attempts reached, moving to dead letter queue: {$job['id']}\n";
            $this->queue->pushToDeadLetter($job);
            $this->logFailedJob($job, $e);
        } else {
            echo "Retrying job ({$job['attempts']}/{$this->maxAttempts}): {$job['id']}\n";
            // Push back to queue for retry
            $this->queue->push($job['queue'], $job['payload']);
        }
    }

    /**
     * Log failed job
     */
    private function logFailedJob(array $job, \Exception $e): void
    {
        $logDir = __DIR__ . '/../../storage/logs';
        
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $logFile = $logDir . '/failed_jobs.log';
        
        $logEntry = sprintf(
            "[%s] Job ID: %s, Queue: %s, Attempts: %d, Error: %s\n",
            date('Y-m-d H:i:s'),
            $job['id'],
            $job['queue'],
            $job['attempts'],
            $e->getMessage()
        );

        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }

    /**
     * Stop worker
     */
    public function stop(): void
    {
        $this->running = false;
        echo "Worker stopping...\n";
    }

    /**
     * Process single job from queue (for CLI)
     */
    public function processOne(string $queueName): bool
    {
        $job = $this->queue->pop($queueName);

        if ($job === null) {
            echo "No jobs in queue\n";
            return false;
        }

        $this->processJob($job);
        return true;
    }
}
