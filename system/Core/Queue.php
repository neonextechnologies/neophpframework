<?php

namespace NeoCore\System\Core;

/**
 * Queue - Job queue system
 * 
 * Supports Redis or file-based storage.
 * No magic. Explicit job pushing and processing.
 */
class Queue
{
    private string $driver;
    private array $config;
    private $connection = null;

    public function __construct(string $driver = 'file', array $config = [])
    {
        $this->driver = $driver;
        $this->config = $config;
        $this->connect();
    }

    /**
     * Connect to queue storage
     */
    private function connect(): void
    {
        if ($this->driver === 'redis') {
            $this->connectRedis();
        }
        // File driver doesn't need connection
    }

    /**
     * Connect to Redis
     */
    private function connectRedis(): void
    {
        if (!extension_loaded('redis')) {
            throw new \Exception('Redis extension not loaded');
        }

        $this->connection = new \Redis();
        $host = $this->config['host'] ?? '127.0.0.1';
        $port = $this->config['port'] ?? 6379;
        
        $this->connection->connect($host, $port);
        
        if (isset($this->config['password'])) {
            $this->connection->auth($this->config['password']);
        }
        
        if (isset($this->config['database'])) {
            $this->connection->select($this->config['database']);
        }
    }

    /**
     * Push job to queue
     */
    public function push(string $queue, array $job): bool
    {
        $jobData = [
            'id' => $this->generateJobId(),
            'queue' => $queue,
            'payload' => $job,
            'attempts' => 0,
            'created_at' => time()
        ];

        if ($this->driver === 'redis') {
            return $this->pushToRedis($queue, $jobData);
        }

        return $this->pushToFile($queue, $jobData);
    }

    /**
     * Push job to Redis
     */
    private function pushToRedis(string $queue, array $jobData): bool
    {
        $key = "queue:$queue";
        return $this->connection->rPush($key, json_encode($jobData)) !== false;
    }

    /**
     * Push job to file
     */
    private function pushToFile(string $queue, array $jobData): bool
    {
        $queueDir = $this->config['path'] ?? __DIR__ . '/../../storage/queue';
        $queueFile = "$queueDir/$queue.queue";

        if (!is_dir($queueDir)) {
            mkdir($queueDir, 0755, true);
        }

        $data = json_encode($jobData) . "\n";
        return file_put_contents($queueFile, $data, FILE_APPEND | LOCK_EX) !== false;
    }

    /**
     * Pop job from queue
     */
    public function pop(string $queue): ?array
    {
        if ($this->driver === 'redis') {
            return $this->popFromRedis($queue);
        }

        return $this->popFromFile($queue);
    }

    /**
     * Pop job from Redis
     */
    private function popFromRedis(string $queue): ?array
    {
        $key = "queue:$queue";
        $jobJson = $this->connection->lPop($key);

        if ($jobJson === false) {
            return null;
        }

        return json_decode($jobJson, true);
    }

    /**
     * Pop job from file
     */
    private function popFromFile(string $queue): ?array
    {
        $queueDir = $this->config['path'] ?? __DIR__ . '/../../storage/queue';
        $queueFile = "$queueDir/$queue.queue";

        if (!file_exists($queueFile)) {
            return null;
        }

        $fp = fopen($queueFile, 'r+');
        if (!$fp) {
            return null;
        }

        if (flock($fp, LOCK_EX)) {
            $lines = [];
            $firstJob = null;

            while (($line = fgets($fp)) !== false) {
                if ($firstJob === null && trim($line) !== '') {
                    $firstJob = json_decode(trim($line), true);
                } else {
                    $lines[] = $line;
                }
            }

            // Rewrite file without first job
            ftruncate($fp, 0);
            rewind($fp);
            fwrite($fp, implode('', $lines));
            flock($fp, LOCK_UN);
        }

        fclose($fp);
        return $firstJob;
    }

    /**
     * Get queue size
     */
    public function size(string $queue): int
    {
        if ($this->driver === 'redis') {
            $key = "queue:$queue";
            return (int)$this->connection->lLen($key);
        }

        $queueDir = $this->config['path'] ?? __DIR__ . '/../../storage/queue';
        $queueFile = "$queueDir/$queue.queue";

        if (!file_exists($queueFile)) {
            return 0;
        }

        $lines = file($queueFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        return count($lines);
    }

    /**
     * Clear queue
     */
    public function clear(string $queue): bool
    {
        if ($this->driver === 'redis') {
            $key = "queue:$queue";
            return $this->connection->del($key) !== false;
        }

        $queueDir = $this->config['path'] ?? __DIR__ . '/../../storage/queue';
        $queueFile = "$queueDir/$queue.queue";

        if (file_exists($queueFile)) {
            return unlink($queueFile);
        }

        return true;
    }

    /**
     * Push job to dead letter queue
     */
    public function pushToDeadLetter(array $job): bool
    {
        return $this->push('dead-letter', $job);
    }

    /**
     * Generate unique job ID
     */
    private function generateJobId(): string
    {
        return uniqid('job_', true);
    }
}
