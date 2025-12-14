<?php

declare(strict_types=1);

namespace NeoCore\Schedule;

use Closure;

/**
 * Scheduled Task
 * 
 * Represents a single scheduled task
 */
class ScheduledTask
{
    protected string $expression = '* * * * *';
    protected string|Closure|null $command = null;
    protected ?string $description = null;
    protected bool $preventOverlap = false;
    protected bool $runInBackground = false;
    protected bool $withoutOverlapping = false;
    protected ?int $expiresAt = null;
    protected ?Closure $when = null;
    protected ?Closure $skip = null;
    protected array $environments = [];
    protected array $filters = [];
    protected array $rejects = [];

    public function __construct(string|Closure $command)
    {
        $this->command = $command;
    }

    /**
     * Set cron expression
     */
    public function cron(string $expression): static
    {
        $this->expression = $expression;
        return $this;
    }

    /**
     * Schedule to run every minute
     */
    public function everyMinute(): static
    {
        return $this->cron('* * * * *');
    }

    /**
     * Schedule to run every five minutes
     */
    public function everyFiveMinutes(): static
    {
        return $this->cron('*/5 * * * *');
    }

    /**
     * Schedule to run every ten minutes
     */
    public function everyTenMinutes(): static
    {
        return $this->cron('*/10 * * * *');
    }

    /**
     * Schedule to run every fifteen minutes
     */
    public function everyFifteenMinutes(): static
    {
        return $this->cron('*/15 * * * *');
    }

    /**
     * Schedule to run every thirty minutes
     */
    public function everyThirtyMinutes(): static
    {
        return $this->cron('0,30 * * * *');
    }

    /**
     * Schedule to run hourly
     */
    public function hourly(): static
    {
        return $this->cron('0 * * * *');
    }

    /**
     * Schedule to run hourly at a specific minute
     */
    public function hourlyAt(int $minute): static
    {
        return $this->cron("{$minute} * * * *");
    }

    /**
     * Schedule to run daily
     */
    public function daily(): static
    {
        return $this->cron('0 0 * * *');
    }

    /**
     * Schedule to run daily at a specific time
     */
    public function dailyAt(string $time): static
    {
        $segments = explode(':', $time);
        $hour = (int) $segments[0];
        $minute = isset($segments[1]) ? (int) $segments[1] : 0;

        return $this->cron("{$minute} {$hour} * * *");
    }

    /**
     * Schedule to run twice daily
     */
    public function twiceDaily(int $first = 1, int $second = 13): static
    {
        return $this->cron("0 {$first},{$second} * * *");
    }

    /**
     * Schedule to run weekly
     */
    public function weekly(): static
    {
        return $this->cron('0 0 * * 0');
    }

    /**
     * Schedule to run weekly on a specific day and time
     */
    public function weeklyOn(int $dayOfWeek, string $time = '0:0'): static
    {
        $segments = explode(':', $time);
        $hour = (int) $segments[0];
        $minute = isset($segments[1]) ? (int) $segments[1] : 0;

        return $this->cron("{$minute} {$hour} * * {$dayOfWeek}");
    }

    /**
     * Schedule to run monthly
     */
    public function monthly(): static
    {
        return $this->cron('0 0 1 * *');
    }

    /**
     * Schedule to run monthly on a specific day and time
     */
    public function monthlyOn(int $dayOfMonth = 1, string $time = '0:0'): static
    {
        $segments = explode(':', $time);
        $hour = (int) $segments[0];
        $minute = isset($segments[1]) ? (int) $segments[1] : 0;

        return $this->cron("{$minute} {$hour} {$dayOfMonth} * *");
    }

    /**
     * Schedule to run quarterly
     */
    public function quarterly(): static
    {
        return $this->cron('0 0 1 */3 *');
    }

    /**
     * Schedule to run yearly
     */
    public function yearly(): static
    {
        return $this->cron('0 0 1 1 *');
    }

    /**
     * Schedule to run on weekdays
     */
    public function weekdays(): static
    {
        return $this->cron('0 0 * * 1-5');
    }

    /**
     * Schedule to run on weekends
     */
    public function weekends(): static
    {
        return $this->cron('0 0 * * 0,6');
    }

    /**
     * Schedule to run on Sundays
     */
    public function sundays(): static
    {
        return $this->cron('0 0 * * 0');
    }

    /**
     * Schedule to run on Mondays
     */
    public function mondays(): static
    {
        return $this->cron('0 0 * * 1');
    }

    /**
     * Schedule to run on Tuesdays
     */
    public function tuesdays(): static
    {
        return $this->cron('0 0 * * 2');
    }

    /**
     * Schedule to run on Wednesdays
     */
    public function wednesdays(): static
    {
        return $this->cron('0 0 * * 3');
    }

    /**
     * Schedule to run on Thursdays
     */
    public function thursdays(): static
    {
        return $this->cron('0 0 * * 4');
    }

    /**
     * Schedule to run on Fridays
     */
    public function fridays(): static
    {
        return $this->cron('0 0 * * 5');
    }

    /**
     * Schedule to run on Saturdays
     */
    public function saturdays(): static
    {
        return $this->cron('0 0 * * 6');
    }

    /**
     * Set description
     */
    public function description(string $description): static
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Prevent task overlap
     */
    public function withoutOverlapping(int $expiresAt = 1440): static
    {
        $this->withoutOverlapping = true;
        $this->expiresAt = $expiresAt;
        return $this;
    }

    /**
     * Run in background
     */
    public function runInBackground(): static
    {
        $this->runInBackground = true;
        return $this;
    }

    /**
     * Only run in specific environments
     */
    public function environments(array|string $environments): static
    {
        $this->environments = is_array($environments) ? $environments : func_get_args();
        return $this;
    }

    /**
     * Add conditional filter
     */
    public function when(Closure $callback): static
    {
        $this->filters[] = $callback;
        return $this;
    }

    /**
     * Add conditional skip
     */
    public function skip(Closure $callback): static
    {
        $this->rejects[] = $callback;
        return $this;
    }

    /**
     * Get command
     */
    public function getCommand(): string|Closure|null
    {
        return $this->command;
    }

    /**
     * Get expression
     */
    public function getExpression(): string
    {
        return $this->expression;
    }

    /**
     * Get description
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Check if task is due to run
     */
    public function isDue(): bool
    {
        $cron = new CronExpression($this->expression);
        return $cron->isDue();
    }

    /**
     * Check if task should run
     */
    public function shouldRun(): bool
    {
        if (!$this->isDue()) {
            return false;
        }

        foreach ($this->filters as $filter) {
            if (!$filter()) {
                return false;
            }
        }

        foreach ($this->rejects as $reject) {
            if ($reject()) {
                return false;
            }
        }

        if (!empty($this->environments)) {
            $environment = env('APP_ENV', 'production');
            if (!in_array($environment, $this->environments)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if task should prevent overlap
     */
    public function shouldPreventOverlap(): bool
    {
        return $this->withoutOverlapping;
    }

    /**
     * Get lock expires time
     */
    public function getExpiresAt(): ?int
    {
        return $this->expiresAt;
    }

    /**
     * Check if should run in background
     */
    public function shouldRunInBackground(): bool
    {
        return $this->runInBackground;
    }
}
