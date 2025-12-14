<?php

declare(strict_types=1);

namespace NeoCore\Schedule;

use DateTime;
use DateTimeZone;
use InvalidArgumentException;

/**
 * Cron Expression Parser
 * 
 * Parses and evaluates cron expressions
 * Supports: *, -, /, L, W, # and ranges
 */
class CronExpression
{
    protected string $expression;
    protected array $segments;

    protected const MINUTE = 0;
    protected const HOUR = 1;
    protected const DAY = 2;
    protected const MONTH = 3;
    protected const WEEKDAY = 4;

    protected static array $monthMapping = [
        'JAN' => 1, 'FEB' => 2, 'MAR' => 3, 'APR' => 4,
        'MAY' => 5, 'JUN' => 6, 'JUL' => 7, 'AUG' => 8,
        'SEP' => 9, 'OCT' => 10, 'NOV' => 11, 'DEC' => 12,
    ];

    protected static array $weekdayMapping = [
        'SUN' => 0, 'MON' => 1, 'TUE' => 2, 'WED' => 3,
        'THU' => 4, 'FRI' => 5, 'SAT' => 6,
    ];

    public function __construct(string $expression)
    {
        $this->expression = $expression;
        $this->segments = $this->parseExpression($expression);
    }

    /**
     * Parse cron expression
     */
    protected function parseExpression(string $expression): array
    {
        $parts = preg_split('/\s+/', trim($expression));

        if (count($parts) !== 5) {
            throw new InvalidArgumentException("Invalid cron expression: {$expression}");
        }

        return [
            self::MINUTE => $parts[0],
            self::HOUR => $parts[1],
            self::DAY => $parts[2],
            self::MONTH => $parts[3],
            self::WEEKDAY => $parts[4],
        ];
    }

    /**
     * Check if expression is due to run
     */
    public function isDue(?DateTime $currentTime = null): bool
    {
        $currentTime = $currentTime ?? new DateTime('now');

        return $this->matchesMinute($currentTime) &&
               $this->matchesHour($currentTime) &&
               $this->matchesDay($currentTime) &&
               $this->matchesMonth($currentTime) &&
               $this->matchesWeekday($currentTime);
    }

    /**
     * Get next run date
     */
    public function getNextRunDate(?DateTime $currentTime = null): DateTime
    {
        $currentTime = $currentTime ?? new DateTime('now');
        $nextRun = clone $currentTime;
        $nextRun->modify('+1 minute');
        $nextRun->setSeconds(0);

        // Limit iterations to prevent infinite loops
        $maxIterations = 525600; // One year in minutes
        $iterations = 0;

        while (!$this->isDue($nextRun) && $iterations < $maxIterations) {
            $nextRun->modify('+1 minute');
            $iterations++;
        }

        if ($iterations >= $maxIterations) {
            throw new \RuntimeException("Could not calculate next run date for expression: {$this->expression}");
        }

        return $nextRun;
    }

    /**
     * Match minute segment
     */
    protected function matchesMinute(DateTime $time): bool
    {
        return $this->matchesSegment(
            self::MINUTE,
            (int) $time->format('i'),
            0,
            59
        );
    }

    /**
     * Match hour segment
     */
    protected function matchesHour(DateTime $time): bool
    {
        return $this->matchesSegment(
            self::HOUR,
            (int) $time->format('G'),
            0,
            23
        );
    }

    /**
     * Match day segment
     */
    protected function matchesDay(DateTime $time): bool
    {
        $segment = $this->segments[self::DAY];

        // Last day of month
        if ($segment === 'L') {
            return $time->format('j') === $time->format('t');
        }

        // Nearest weekday (W)
        if (preg_match('/^(\d+)W$/', $segment, $matches)) {
            return $this->matchesNearestWeekday($time, (int) $matches[1]);
        }

        return $this->matchesSegment(
            self::DAY,
            (int) $time->format('j'),
            1,
            31
        );
    }

    /**
     * Match month segment
     */
    protected function matchesMonth(DateTime $time): bool
    {
        return $this->matchesSegment(
            self::MONTH,
            (int) $time->format('n'),
            1,
            12
        );
    }

    /**
     * Match weekday segment
     */
    protected function matchesWeekday(DateTime $time): bool
    {
        $segment = $this->segments[self::WEEKDAY];
        $weekday = (int) $time->format('w');

        // Nth occurrence of weekday in month (e.g., 1#2 = second Monday)
        if (preg_match('/^(\d+)#(\d+)$/', $segment, $matches)) {
            $targetWeekday = (int) $matches[1];
            $occurrence = (int) $matches[2];

            if ($weekday !== $targetWeekday) {
                return false;
            }

            $currentOccurrence = ceil((int) $time->format('j') / 7);
            return $currentOccurrence === $occurrence;
        }

        // Last occurrence of weekday in month
        if (preg_match('/^(\d+)L$/', $segment, $matches)) {
            $targetWeekday = (int) $matches[1];

            if ($weekday !== $targetWeekday) {
                return false;
            }

            $daysInMonth = (int) $time->format('t');
            $currentDay = (int) $time->format('j');

            return $currentDay + 7 > $daysInMonth;
        }

        return $this->matchesSegment(
            self::WEEKDAY,
            $weekday,
            0,
            7 // Allow both 0 and 7 for Sunday
        );
    }

    /**
     * Match a segment value
     */
    protected function matchesSegment(int $segment, int $value, int $min, int $max): bool
    {
        $expression = $this->segments[$segment];

        // Replace month/weekday names
        if ($segment === self::MONTH) {
            $expression = $this->replaceMapping($expression, self::$monthMapping);
        } elseif ($segment === self::WEEKDAY) {
            $expression = $this->replaceMapping($expression, self::$weekdayMapping);
            // Normalize Sunday (0 and 7 are both Sunday)
            if ($value === 0) {
                $value = 7;
            }
        }

        // Wildcard
        if ($expression === '*' || $expression === '?') {
            return true;
        }

        // List (e.g., 1,3,5)
        if (strpos($expression, ',') !== false) {
            $parts = explode(',', $expression);
            foreach ($parts as $part) {
                if ($this->matchesSingleSegment($part, $value, $min, $max)) {
                    return true;
                }
            }
            return false;
        }

        return $this->matchesSingleSegment($expression, $value, $min, $max);
    }

    /**
     * Match a single segment expression
     */
    protected function matchesSingleSegment(string $expression, int $value, int $min, int $max): bool
    {
        // Step values (e.g., */5)
        if (preg_match('/^(\*|\d+-\d+)\/(\d+)$/', $expression, $matches)) {
            $step = (int) $matches[2];

            if ($matches[1] === '*') {
                return ($value - $min) % $step === 0;
            }

            // Range with step (e.g., 10-20/2)
            [$rangeMin, $rangeMax] = explode('-', $matches[1]);
            $rangeMin = (int) $rangeMin;
            $rangeMax = (int) $rangeMax;

            if ($value < $rangeMin || $value > $rangeMax) {
                return false;
            }

            return ($value - $rangeMin) % $step === 0;
        }

        // Range (e.g., 1-5)
        if (preg_match('/^(\d+)-(\d+)$/', $expression, $matches)) {
            $rangeMin = (int) $matches[1];
            $rangeMax = (int) $matches[2];
            return $value >= $rangeMin && $value <= $rangeMax;
        }

        // Exact match
        return (int) $expression === $value;
    }

    /**
     * Check if date matches nearest weekday
     */
    protected function matchesNearestWeekday(DateTime $time, int $targetDay): bool
    {
        $daysInMonth = (int) $time->format('t');
        $targetDay = min($targetDay, $daysInMonth);

        $targetDate = DateTime::createFromFormat('Y-m-d', $time->format("Y-m-{$targetDay}"));
        $weekday = (int) $targetDate->format('w');

        // If target is weekend, find nearest weekday
        if ($weekday === 0) { // Sunday -> Friday
            $targetDate->modify('-2 days');
        } elseif ($weekday === 6) { // Saturday -> Friday
            $targetDate->modify('-1 day');
        }

        return $time->format('Y-m-d') === $targetDate->format('Y-m-d');
    }

    /**
     * Replace month/weekday names with numbers
     */
    protected function replaceMapping(string $expression, array $mapping): string
    {
        foreach ($mapping as $name => $number) {
            $expression = str_ireplace($name, (string) $number, $expression);
        }
        return $expression;
    }

    /**
     * Get expression string
     */
    public function getExpression(): string
    {
        return $this->expression;
    }

    /**
     * Get human-readable description
     */
    public function getDescription(): string
    {
        $descriptions = [];

        // Minute
        $minute = $this->segments[self::MINUTE];
        if ($minute === '*') {
            $descriptions[] = 'every minute';
        } elseif (preg_match('/^\*\/(\d+)$/', $minute, $matches)) {
            $descriptions[] = "every {$matches[1]} minutes";
        } else {
            $descriptions[] = "at minute {$minute}";
        }

        // Hour
        $hour = $this->segments[self::HOUR];
        if ($hour !== '*') {
            $descriptions[] = "at hour {$hour}";
        }

        // Day
        $day = $this->segments[self::DAY];
        if ($day !== '*') {
            $descriptions[] = "on day {$day}";
        }

        // Month
        $month = $this->segments[self::MONTH];
        if ($month !== '*') {
            $descriptions[] = "in month {$month}";
        }

        // Weekday
        $weekday = $this->segments[self::WEEKDAY];
        if ($weekday !== '*') {
            $descriptions[] = "on weekday {$weekday}";
        }

        return implode(', ', $descriptions);
    }
}
