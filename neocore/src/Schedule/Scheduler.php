<?php

declare(strict_types=1);

namespace NeoCore\Schedule;

use Closure;

/**
 * Task Scheduler
 * 
 * Manages scheduled tasks and their execution
 */
class Scheduler
{
    protected array $tasks = [];
    protected ?string $timezone = null;

    public function __construct(?string $timezone = null)
    {
        $this->timezone = $timezone ?? date_default_timezone_get();
    }

    /**
     * Schedule a command
     */
    public function command(string $command): ScheduledTask
    {
        $task = new ScheduledTask($command);
        $this->tasks[] = $task;
        return $task;
    }

    /**
     * Schedule a callable
     */
    public function call(Closure $callback, array $parameters = []): ScheduledTask
    {
        $task = new ScheduledTask(function() use ($callback, $parameters) {
            return call_user_func_array($callback, $parameters);
        });
        $this->tasks[] = $task;
        return $task;
    }

    /**
     * Schedule a job
     */
    public function job(string $job, array $data = []): ScheduledTask
    {
        $task = new ScheduledTask(function() use ($job, $data) {
            $instance = new $job();
            return $instance->handle($data);
        });
        $this->tasks[] = $task;
        return $task;
    }

    /**
     * Schedule with raw cron expression
     */
    public function cron(string $expression, string|Closure $command): ScheduledTask
    {
        $task = new ScheduledTask($command);
        $task->cron($expression);
        $this->tasks[] = $task;
        return $task;
    }

    /**
     * Get all tasks
     */
    public function getTasks(): array
    {
        return $this->tasks;
    }

    /**
     * Get due tasks
     */
    public function getDueTasks(): array
    {
        return array_filter($this->tasks, function(ScheduledTask $task) {
            return $task->shouldRun();
        });
    }

    /**
     * Run all due tasks
     */
    public function run(): array
    {
        $dueTasks = $this->getDueTasks();
        $results = [];

        foreach ($dueTasks as $task) {
            $results[] = $this->runTask($task);
        }

        return $results;
    }

    /**
     * Run a single task
     */
    protected function runTask(ScheduledTask $task): array
    {
        $command = $task->getCommand();
        $startTime = microtime(true);
        $result = [
            'task' => $task->getDescription() ?? (is_string($command) ? $command : 'Closure'),
            'started_at' => date('Y-m-d H:i:s'),
            'success' => false,
            'output' => null,
            'error' => null,
            'duration' => 0,
        ];

        try {
            if ($task->shouldPreventOverlap()) {
                $mutex = new TaskMutex();
                $lockKey = $this->getTaskLockKey($task);

                if (!$mutex->create($lockKey, $task->getExpiresAt() ?? 1440)) {
                    $result['error'] = 'Task is already running';
                    return $result;
                }

                try {
                    $result['output'] = $this->executeTask($task);
                    $result['success'] = true;
                } finally {
                    $mutex->forget($lockKey);
                }
            } else {
                $result['output'] = $this->executeTask($task);
                $result['success'] = true;
            }
        } catch (\Throwable $e) {
            $result['error'] = $e->getMessage();
            $result['success'] = false;
        }

        $result['duration'] = round(microtime(true) - $startTime, 2);
        return $result;
    }

    /**
     * Execute task
     */
    protected function executeTask(ScheduledTask $task): mixed
    {
        $command = $task->getCommand();

        if ($command instanceof Closure) {
            return $command();
        }

        if (is_string($command)) {
            // Run CLI command
            if ($task->shouldRunInBackground()) {
                $this->runInBackground($command);
                return 'Running in background';
            } else {
                return $this->runCommand($command);
            }
        }

        return null;
    }

    /**
     * Run command
     */
    protected function runCommand(string $command): string
    {
        $output = [];
        $returnCode = 0;

        exec($command, $output, $returnCode);

        return implode("\n", $output);
    }

    /**
     * Run command in background
     */
    protected function runInBackground(string $command): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            pclose(popen("start /B {$command}", 'r'));
        } else {
            exec("{$command} > /dev/null 2>&1 &");
        }
    }

    /**
     * Get task lock key
     */
    protected function getTaskLockKey(ScheduledTask $task): string
    {
        $command = $task->getCommand();
        
        if (is_string($command)) {
            return 'schedule:' . md5($command);
        }

        return 'schedule:' . md5(spl_object_hash($command));
    }

    /**
     * Clear all tasks
     */
    public function clearTasks(): void
    {
        $this->tasks = [];
    }

    /**
     * Set timezone
     */
    public function setTimezone(string $timezone): void
    {
        $this->timezone = $timezone;
        date_default_timezone_set($timezone);
    }

    /**
     * Get timezone
     */
    public function getTimezone(): string
    {
        return $this->timezone;
    }
}
